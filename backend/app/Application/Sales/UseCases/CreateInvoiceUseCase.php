<?php

declare(strict_types=1);

namespace App\Application\Sales\UseCases;

use App\Application\Sales\DTOs\CreateInvoiceDTO;
use App\Domain\Sales\Entities\Invoice;
use App\Domain\Sales\Entities\InvoiceItem;
use App\Domain\Sales\Repositories\InvoiceRepositoryInterface;
use App\Domain\Sales\Rules\InvoiceRules;
use App\Domain\Inventory\Repositories\ProductRepositoryInterface;
use App\Domain\Accounting\Repositories\JournalEntryRepositoryInterface;
use App\Domain\Accounting\Entities\JournalEntry;
use App\Domain\Accounting\Entities\JournalEntryLine;
use App\Domain\Sales\Services\ZatcaPhase1Service;
use App\Jobs\SubmitZatcaInvoiceJob;

/**
 * CreateInvoiceUseCase
 * 
 * Orchestrates invoice creation:
 * 1. Generates invoice number
 * 2. Creates invoice with items
 * 3. Validates business rules
 * 4. Deducts stock from warehouse
 * 5. Creates automatic journal entries
 * 6. Updates customer balance (for credit invoices)
 */
final class CreateInvoiceUseCase
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private ProductRepositoryInterface $productRepository,
        private JournalEntryRepositoryInterface $journalEntryRepository,
        private ZatcaPhase1Service $zatcaPhase1Service,
    ) {}

    public function execute(CreateInvoiceDTO $dto, string $userId): Invoice
    {
        // 1. Generate invoice number
        $invoiceNumber = $this->invoiceRepository->getNextInvoiceNumber();

        // 2. Build invoice items
        $items = [];
        foreach ($dto->items as $itemDTO) {
            $items[] = new InvoiceItem(
                id: null,
                invoiceId: '', // Will be set after invoice creation
                productId: $itemDTO->productId,
                quantity: $itemDTO->quantity,
                unitPrice: $itemDTO->unitPrice,
                discountPercent: $itemDTO->discountPercent,
                vatRate: $itemDTO->vatRate,
            );
        }

        // 3. Create invoice entity
        $invoice = new Invoice(
            id: null,
            invoiceNumber: $invoiceNumber,
            customerId: $dto->customerId,
            type: $dto->type,
            subtotal: 0,
            vatAmount: 0,
            discountAmount: 0,
            total: 0,
            status: 'draft',
            notes: $dto->notes,
            warehouseId: $dto->warehouseId,
            createdBy: $userId,
        );

        $invoice->setItems($items);

        // 4. Validate business rules
        $errors = InvoiceRules::validateForConfirmation($invoice);
        if (!empty($errors)) {
            throw new \DomainException(implode(' ', $errors));
        }

        // 5. Confirm the invoice (changes status from draft → confirmed)
        $invoice->confirm();

        // Generate ZATCA Phase 1 QR Code
        // Ideally seller Name and VAT are pulled from tenant settings. Hardcoding defaults for now.
        $sellerName = 'شركة تجريبية'; // Should be fetched from tenant_settings
        $vatNumber = '300000000000003'; 
        
        $qrCode = $this->zatcaPhase1Service->generateQrBase64(
            $sellerName,
            $vatNumber,
            $invoice->getInvoiceDate(),
            $invoice->getTotal(),
            $invoice->getVatAmount()
        );
        $invoice->setZatcaQrCode($qrCode);

        // 6. Persist invoice
        $savedInvoice = $this->invoiceRepository->create($invoice);

        // 7. Deduct stock for each item
        foreach ($dto->items as $itemDTO) {
            $currentStock = $this->productRepository->getStockLevel(
                $itemDTO->productId,
                $dto->warehouseId
            );

            if ($currentStock < $itemDTO->quantity) {
                throw new \DomainException("Insufficient stock for product: {$itemDTO->productId}");
            }

            $this->productRepository->adjustStock(
                $itemDTO->productId,
                $dto->warehouseId,
                -$itemDTO->quantity,
                $itemDTO->unitPrice
            );
        }

        // 8. Create automatic journal entry
        $this->createJournalEntry($savedInvoice, $userId);

        // 9. Dispatch ZATCA Phase 2 background job for Simulation Reporting
        // Currently tenant ID is implicitly known to the environment or can be extracted. 
        // We'll pass a dummy tenant string since this is a multi-tenant DB structure handled automatically.
        $tenantId = app('currentTenant')->id ?? 'tenant_context';
        SubmitZatcaInvoiceJob::dispatch($savedInvoice->getId(), $tenantId);

        return $savedInvoice;
    }

    private function createJournalEntry(Invoice $invoice, string $userId): void
    {
        $entryNumber = $this->journalEntryRepository->getNextEntryNumber();

        $journalEntry = new JournalEntry(
            id: null,
            entryNumber: $entryNumber,
            date: new \DateTimeImmutable(),
            description: "Sales Invoice: {$invoice->getInvoiceNumber()}",
            isPosted: false,
            referenceType: 'invoice',
            referenceId: $invoice->getId(),
            createdBy: $userId,
        );

        // Debit: Cash/Receivables
        if ($invoice->getType() === 'cash') {
            // Debit Cash account
            $journalEntry->addLine(new JournalEntryLine(
                id: null,
                journalEntryId: '',
                accountId: 'CASH_ACCOUNT_ID', // Will be resolved by config
                debit: $invoice->getTotal(),
                credit: 0,
                description: 'Cash sales',
            ));
        } else {
            // Debit Accounts Receivable
            $journalEntry->addLine(new JournalEntryLine(
                id: null,
                journalEntryId: '',
                accountId: 'AR_ACCOUNT_ID',
                debit: $invoice->getTotal(),
                credit: 0,
                description: 'Credit sales - Accounts Receivable',
            ));
        }

        // Credit: Revenue
        $journalEntry->addLine(new JournalEntryLine(
            id: null,
            journalEntryId: '',
            accountId: 'REVENUE_ACCOUNT_ID',
            debit: 0,
            credit: $invoice->getSubtotal() - $invoice->getDiscountAmount(),
            description: 'Sales revenue',
        ));

        // Credit: VAT Payable
        if ($invoice->getVatAmount() > 0) {
            $journalEntry->addLine(new JournalEntryLine(
                id: null,
                journalEntryId: '',
                accountId: 'VAT_PAYABLE_ACCOUNT_ID',
                debit: 0,
                credit: $invoice->getVatAmount(),
                description: 'VAT payable',
            ));
        }

        $journalEntry->post();
        $this->journalEntryRepository->create($journalEntry);
    }
}
