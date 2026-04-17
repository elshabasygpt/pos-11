<?php

declare(strict_types=1);

namespace App\Presentation\Controllers\API\Sales;

use App\Presentation\Controllers\API\BaseController;
use App\Infrastructure\Eloquent\Models\InvoiceModel;
use App\Infrastructure\Eloquent\Models\InvoiceItemModel;
use App\Infrastructure\Eloquent\Models\WarehouseProductModel;
use App\Infrastructure\Eloquent\Models\StockMovementModel;
use App\Infrastructure\Eloquent\Models\SafeModel;
use App\Infrastructure\Eloquent\Models\SafeTransactionModel;
use App\Infrastructure\Eloquent\Models\CustomerModel;
use App\Infrastructure\Eloquent\Models\ProductModel;
use App\Domain\Sales\Services\ZatcaPhase1Service;
use App\Jobs\SubmitZatcaInvoiceJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $limit = $request->query('limit', '15');
        $status = $request->query('status');
        $invoiceNumber = $request->query('invoice_number');
        
        $query = InvoiceModel::with(['customer', 'items.product', 'creator'])->orderBy('invoice_date', 'desc');
        
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        if ($invoiceNumber) {
            $query->where('invoice_number', $invoiceNumber);
        }

        $invoices = $query->paginate((int) $limit);

        return $this->paginated($invoices->toArray(), 'Sales Invoices retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|uuid|exists:customers,id',
            'warehouse_id' => 'nullable|uuid|exists:warehouses,id',
            'type' => 'nullable|string|in:cash,credit',
            'status' => 'required|string|in:draft,confirmed,cancelled',
            'invoice_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.vat_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            DB::beginTransaction();

            $invoiceId = Str::uuid()->toString();
            $subtotalAmount = 0;
            $taxAmount = 0;
            $discountAmount = 0;
            $totalProfit = 0;

            foreach ($validated['items'] as $item) {
                $product = ProductModel::find($item['product_id']);
                $costPrice = (float) ($product->cost_price ?? 0);
                
                $discountPct = $item['discount_percent'] ?? 0;
                $gross = $item['quantity'] * $item['unit_price'];
                $itemDiscount = $gross * ($discountPct / 100);
                $net = $gross - $itemDiscount;
                $vatRate = $item['vat_rate'] ?? 15;
                $itemTax = $net * ($vatRate / 100);

                $subtotalAmount += $net;
                $taxAmount += $itemTax;
                $discountAmount += $itemDiscount;

                // Profit calculation (Price - Cost) * Qty
                $totalProfit += ($item['unit_price'] - $costPrice) * $item['quantity'];
            }
            $totalAmount = $subtotalAmount + $taxAmount;

            // Calculate Commission based on Net Profit
            $currentUser = auth()->user();
            $commissionRate = (float) ($currentUser->commission_rate ?? 0);
            $commissionAmount = $totalProfit * ($commissionRate / 100);

            $lastInvoice = InvoiceModel::latest('created_at')->first();
            $nextNum = $lastInvoice ? ((int) str_replace('INV-', '', $lastInvoice->invoice_number)) + 1 : 1;
            $invoiceNumber = 'INV-' . str_pad((string)$nextNum, 6, '0', STR_PAD_LEFT);

            $invoice = InvoiceModel::create([
                'id' => $invoiceId,
                'invoice_number' => $invoiceNumber,
                'customer_id' => $validated['customer_id'] ?? null,
                'warehouse_id' => $validated['warehouse_id'] ?? null,
                'type' => $validated['type'] ?? 'cash',
                'invoice_date' => $validated['invoice_date'] ?? now(),
                'subtotal' => $subtotalAmount,
                'vat_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total' => $totalAmount,
                'commission_amount' => $commissionAmount,
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $product = ProductModel::find($item['product_id']);
                $costPrice = (float) ($product->cost_price ?? 0);

                $discountPct = $item['discount_percent'] ?? 0;
                $gross = $item['quantity'] * $item['unit_price'];
                $itemDiscount = $gross * ($discountPct / 100);
                $net = $gross - $itemDiscount;
                $itemTax = $net * ($item['vat_rate'] / 100);

                InvoiceItemModel::create([
                    'id' => Str::uuid()->toString(),
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'cost_price' => $costPrice,
                    'discount_percent' => $discountPct,
                    'vat_rate' => $item['vat_rate'],
                    'total' => $net + $itemTax,
                ]);

                // Update stock if confirmed immediately
                if ($validated['status'] === 'confirmed' && !empty($validated['warehouse_id'])) {
                    $wp = WarehouseProductModel::where(['warehouse_id' => $validated['warehouse_id'], 'product_id' => $item['product_id']])->first();
                    if (!$wp || $wp->quantity < $item['quantity']) {
                        throw new \Exception("Insufficient stock for product " . $item['product_id']);
                    }
                    $wp->quantity -= $item['quantity'];
                    $wp->save();

                    StockMovementModel::create([
                        'id' => Str::uuid()->toString(),
                        'product_id' => $item['product_id'],
                        'warehouse_id' => $validated['warehouse_id'],
                        'type' => 'out',
                        'quantity' => -($item['quantity']),
                        'reference_id' => $invoiceId,
                        'reference_type' => 'sales_invoice',
                        'date' => now(),
                    ]);
                }
            }

            // Fire ZATCA generation if confirmed
            if ($validated['status'] === 'confirmed') {
                $this->processZatca($invoice);
                
                // If it's credit, update customer Balance
                if (($validated['type'] ?? 'cash') === 'credit' && !empty($validated['customer_id'])) {
                    $customer = CustomerModel::find($validated['customer_id']);
                    if ($customer) {
                        $customer->balance += $totalAmount;
                        $customer->save();
                    }
                } else if ($validated['type'] === 'cash') {
                    $this->depositToSafe($invoice);
                }
            }

            DB::commit();

            return $this->success($invoice->load('items'), 'Sales invoice created successfully', 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create sales invoice: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $invoice = InvoiceModel::find($id);

        if (!$invoice) { return $this->error('Sales invoice not found', 404); }
        if ($invoice->status !== 'draft') {
            return $this->error('Cannot modify a confirmed invoice. Please use adjustments or returns.', 422);
        }

        $validated = $request->validate([
            'customer_id' => 'required|uuid|exists:customers,id',
            'warehouse_id' => 'required|uuid|exists:warehouses,id',
            'type' => 'required|string|in:cash,credit',
            'status' => 'required|string|in:draft,confirmed,cancelled',
            'invoice_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.vat_rate' => 'required|numeric|min:0|max:100',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            DB::beginTransaction();
            InvoiceItemModel::where('invoice_id', $invoice->id)->delete();

            $subtotalAmount = 0;
            $taxAmount = 0;
            $discountAmount = 0;

            foreach ($validated['items'] as $item) {
                $discountPct = $item['discount_percent'] ?? 0;
                $gross = $item['quantity'] * $item['unit_price'];
                $itemDiscount = $gross * ($discountPct / 100);
                $net = $gross - $itemDiscount;
                $itemTax = $net * ($item['vat_rate'] / 100);

                $subtotalAmount += $net;
                $taxAmount += $itemTax;
                $discountAmount += $itemDiscount;
            }
            $totalAmount = $subtotalAmount + $taxAmount;

            $invoice->update([
                'customer_id' => $validated['customer_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'type' => $validated['type'],
                'invoice_date' => $validated['invoice_date'] ?? $invoice->invoice_date,
                'subtotal' => $subtotalAmount,
                'vat_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total' => $totalAmount,
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $discountPct = $item['discount_percent'] ?? 0;
                $gross = $item['quantity'] * $item['unit_price'];
                $itemDiscount = $gross * ($discountPct / 100);
                $net = $gross - $itemDiscount;
                $itemTax = $net * ($item['vat_rate'] / 100);

                InvoiceItemModel::create([
                    'id' => Str::uuid()->toString(),
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_percent' => $discountPct,
                    'vat_rate' => $item['vat_rate'],
                    'total' => $net + $itemTax,
                ]);

                if ($validated['status'] === 'confirmed') {
                    $wp = WarehouseProductModel::where(['warehouse_id' => $validated['warehouse_id'], 'product_id' => $item['product_id']])->first();
                    if (!$wp || $wp->quantity < $item['quantity']) {
                        throw new \Exception("Insufficient stock for product " . $item['product_id']);
                    }
                    $wp->quantity -= $item['quantity'];
                    $wp->save();

                    StockMovementModel::create([
                        'id' => Str::uuid()->toString(),
                        'product_id' => $item['product_id'],
                        'warehouse_id' => $validated['warehouse_id'],
                        'type' => 'out',
                        'quantity' => -($item['quantity']),
                        'reference_id' => $invoice->id,
                        'reference_type' => 'sales_invoice',
                        'date' => now(),
                    ]);
                }
            }

            if ($validated['status'] === 'confirmed') {
                $this->processZatca($invoice);
                if ($validated['type'] === 'credit') {
                    $customer = CustomerModel::find($validated['customer_id']);
                    $customer->balance += $totalAmount;
                    $customer->save();
                } else if ($validated['type'] === 'cash') {
                    $this->depositToSafe($invoice);
                }
            }

            DB::commit();
            return $this->success($invoice->load('items'), 'Sales invoice updated successfully', 200);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to update sales invoice: ' . $e->getMessage(), 500);
        }
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $invoice = InvoiceModel::with('items')->find($id);
        if (!$invoice) { return $this->error('Sales invoice not found', 404); }
        
        $validated = $request->validate([
            'status' => 'required|string|in:draft,confirmed,cancelled',
        ]);
        
        if ($invoice->status !== 'confirmed' && $validated['status'] === 'confirmed') {
            DB::beginTransaction();
            try {
                foreach ($invoice->items as $item) {
                    $wp = WarehouseProductModel::where(['warehouse_id' => $invoice->warehouse_id, 'product_id' => $item->product_id])->first();
                    if (!$wp || $wp->quantity < $item->quantity) {
                        throw new \Exception("Insufficient stock for product " . $item->product_id);
                    }
                    $wp->quantity -= $item->quantity;
                    $wp->save();

                    StockMovementModel::create([
                        'id' => Str::uuid()->toString(),
                        'product_id' => $item->product_id,
                        'warehouse_id' => $invoice->warehouse_id,
                        'type' => 'out',
                        'quantity' => -($item->quantity),
                        'reference_id' => $invoice->id,
                        'reference_type' => 'sales_invoice',
                        'date' => now(),
                    ]);
                }
                
                $this->processZatca($invoice);
                
                if ($invoice->type === 'credit') {
                    $customer = CustomerModel::find($invoice->customer_id);
                    $customer->balance += $invoice->total;
                    $customer->save();
                } else if ($invoice->type === 'cash') {
                    $this->depositToSafe($invoice);
                }
                
                $invoice->update(['status' => $validated['status']]);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return $this->error('Failed to update status: ' . $e->getMessage(), 500);
            }
        } else {
            $invoice->update(['status' => $validated['status']]);
        }
        
        return $this->success($invoice, 'Sales invoice status updated successfully');
    }

    public function show(string $id): JsonResponse
    {
        $invoice = InvoiceModel::with(['customer', 'items.product', 'shippingInvoices'])->find($id);
        if (!$invoice) { return $this->error('Sales invoice not found', 404); }
        return $this->success($invoice->toArray());
    }

    public function bulkStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invoices' => 'required|array|min:1',
        ]);

        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        foreach ($validated['invoices'] as $index => $invoiceData) {
            try {
                $req = new Request();
                $req->replace($invoiceData);
                $response = $this->store($req);
                
                if ($response->getStatusCode() === 201) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][$index] = json_decode((string) $response->getContent(), true)['message'] ?? 'Unknown error';
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][$index] = $e->getMessage();
            }
        }

        return $this->success($results, 'Bulk sync completed');
    }

    public function salesReport(Request $request): JsonResponse
    {
        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to = $request->query('to', now()->endOfMonth()->toDateString());

        $query = InvoiceModel::whereBetween('invoice_date', [$from, $to])
            ->where('status', '!=', 'cancelled');

        $report = [
            'total_sales' => (clone $query)->sum('total'),
            'total_tax' => (clone $query)->sum('vat_amount'),
            'total_discount' => (clone $query)->sum('discount_amount'),
            'invoice_count' => (clone $query)->count(),
            'daily_sales' => (clone $query)
                ->select(DB::raw('DATE(invoice_date) as date'), DB::raw('SUM(total) as total'))
                ->groupBy('date')
                ->orderBy('date')
                ->get()
        ];

        return $this->success($report, 'Sales report generated');
    }

    private function processZatca(InvoiceModel $invoice)
    {
        // Try resolving safely. Not critical for core save logic but highly important for KSA.
        try {
            $zatcaPhase1Service = app(ZatcaPhase1Service::class);
            $sellerName = 'محلاتك للتجارة'; 
            $vatNumber = '300000000000003'; 
            $invoiceDateString = $invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s');
            $qrCode = $zatcaPhase1Service->generateQrBase64(
                $sellerName,
                $vatNumber,
                new \DateTimeImmutable($invoiceDateString),
                (float) $invoice->total,
                (float) $invoice->vat_amount
            );
            $invoice->zatca_qr_code = $qrCode;
            $invoice->save();
        } catch (\Exception $e) {
            // Ignored for now.
        }
    }

    private function depositToSafe(InvoiceModel $invoice)
    {
        // Try getting the primary safe for current user
        $safeUserId = auth()->id();
        $safeId = DB::table('safe_users')
            ->where('user_id', $safeUserId)
            ->where('is_primary', true)
            ->value('safe_id');
        
        if (!$safeId) {
            // Pick the first cash safe as fallback
            $safeId = SafeModel::where('type', 'cash')->value('id');
        }

        if ($safeId) {
            $safe = SafeModel::find($safeId);
            if ($safe) {
                $safe->balance += $invoice->total;
                $safe->save();

                SafeTransactionModel::create([
                    'id' => Str::uuid()->toString(),
                    'safe_id' => $safe->id,
                    'type' => 'deposit',
                    'amount' => $invoice->total,
                    'description' => 'أرباح نقدية لفاتورة رقم: ' . ($invoice->invoice_number ?? $invoice->id),
                    'reference_type' => 'sales_invoice',
                    'reference_id' => $invoice->id,
                    'transaction_date' => now(),
                ]);
            }
        }
    }
}
