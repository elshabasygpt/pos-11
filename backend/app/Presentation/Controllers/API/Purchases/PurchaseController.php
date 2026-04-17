<?php

declare(strict_types=1);

namespace App\Presentation\Controllers\API\Purchases;

use App\Presentation\Controllers\API\BaseController;
use App\Infrastructure\Eloquent\Models\PurchaseInvoiceModel;
use App\Infrastructure\Eloquent\Models\PurchaseInvoiceItemModel;
use App\Infrastructure\Eloquent\Models\ProductModel;
use App\Infrastructure\Eloquent\Models\StockMovementModel;
use App\Infrastructure\Eloquent\Models\SupplierModel;
use App\Infrastructure\Eloquent\Models\WarehouseProductModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $limit = $request->query('limit', '15');
        $status = $request->query('status');
        
        $query = PurchaseInvoiceModel::with(['supplier'])->orderBy('invoice_date', 'desc');
        
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $purchases = $query->paginate((int) $limit);

        return $this->paginated($purchases->toArray(), 'Purchases retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'required|uuid|exists:suppliers,id',
            'warehouse_id' => 'required|uuid|exists:warehouses,id',
            'issue_date' => 'required|date',
            'status' => 'required|string|in:draft,confirmed,cancelled',
            'payment_type' => 'required|string|in:cash,credit',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'required|numeric|min:0|max:100',
        ]);

        try {
            DB::beginTransaction();

            $invoiceId = Str::uuid()->toString();
            $subtotalAmount = 0;
            $taxAmount = 0;

            foreach ($validated['items'] as $item) {
                $itemSub = $item['quantity'] * $item['unit_price'];
                $itemTax = $itemSub * ($item['tax_rate'] / 100);
                $subtotalAmount += $itemSub;
                $taxAmount += $itemTax;
            }
            $totalAmount = $subtotalAmount + $taxAmount;

            $paidAmount = $validated['payment_type'] === 'cash' ? $totalAmount : 0;

            $lastInvoice = PurchaseInvoiceModel::latest('created_at')->first();
            $nextNum = $lastInvoice ? ((int) str_replace('PO-', '', $lastInvoice->invoice_number)) + 1 : 1;
            $invoiceNumber = 'PO-' . str_pad((string)$nextNum, 6, '0', STR_PAD_LEFT);

            $purchase = PurchaseInvoiceModel::create([
                'id' => $invoiceId,
                'invoice_number' => $invoiceNumber,
                'supplier_id' => $validated['supplier_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'invoice_date' => $validated['issue_date'],
                'subtotal' => $subtotalAmount,
                'vat_amount' => $taxAmount,
                'total' => $totalAmount,
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $itemSub = $item['quantity'] * $item['unit_price'];
                $itemTax = $itemSub * ($item['tax_rate'] / 100);

                PurchaseInvoiceItemModel::create([
                    'id' => Str::uuid()->toString(),
                    'purchase_invoice_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'vat_rate' => $item['tax_rate'],
                    'total' => $itemSub + $itemTax,
                ]);

                // Update stock if confirmed
                if ($validated['status'] === 'confirmed') {
                    $wp = WarehouseProductModel::firstOrCreate(
                        ['warehouse_id' => $validated['warehouse_id'], 'product_id' => $item['product_id']],
                        ['id' => Str::uuid()->toString(), 'quantity' => 0]
                    );
                    $wp->quantity += $item['quantity'];
                    $wp->save();

                    StockMovementModel::create([
                        'id' => Str::uuid()->toString(),
                        'product_id' => $item['product_id'],
                        'warehouse_id' => $validated['warehouse_id'],
                        'type' => 'in',
                        'quantity' => $item['quantity'],
                        'reference_id' => $invoiceId,
                        'reference_type' => 'purchase_invoice',
                        'date' => now(),
                    ]);
                }
            }

            // Update Supplier Balance for unpaid
            if ($validated['status'] === 'confirmed') {
                $supplier = SupplierModel::find($validated['supplier_id']);
                $owedAmount = $totalAmount - $paidAmount;
                if ($owedAmount > 0) {
                    $supplier->balance += $owedAmount;
                    $supplier->save();
                }
            }

            DB::commit();

            return $this->success($purchase->load('items'), 'Purchase invoice created successfully', 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create purchase invoice: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $purchase = PurchaseInvoiceModel::find($id);

        if (!$purchase) { return $this->error('Purchase invoice not found', 404); }
        if (!in_array($purchase->status, ['draft'])) {
            return $this->error('Cannot modify a confirmed invoice. Please use adjustments or returns.', 422);
        }

        $validated = $request->validate([
            'supplier_id' => 'required|uuid|exists:suppliers,id',
            'warehouse_id' => 'required|uuid|exists:warehouses,id',
            'issue_date' => 'required|date',
            'status' => 'required|string|in:draft,confirmed,cancelled',
            'payment_type' => 'required|string|in:cash,credit',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'required|numeric|min:0|max:100',
        ]);

        try {
            DB::beginTransaction();
            PurchaseInvoiceItemModel::where('purchase_invoice_id', $purchase->id)->delete();

            $subtotalAmount = 0;
            $taxAmount = 0;
            foreach ($validated['items'] as $item) {
                $itemSub = $item['quantity'] * $item['unit_price'];
                $itemTax = $itemSub * ($item['tax_rate'] / 100);
                $subtotalAmount += $itemSub;
                $taxAmount += $itemTax;
            }
            $totalAmount = $subtotalAmount + $taxAmount;
            $paidAmount = $validated['payment_type'] === 'cash' ? $totalAmount : 0;

            $purchase->update([
                'supplier_id' => $validated['supplier_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'invoice_date' => $validated['issue_date'],
                'subtotal' => $subtotalAmount,
                'vat_amount' => $taxAmount,
                'total' => $totalAmount,
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $itemSub = $item['quantity'] * $item['unit_price'];
                $itemTax = $itemSub * ($item['tax_rate'] / 100);

                PurchaseInvoiceItemModel::create([
                    'id' => Str::uuid()->toString(),
                    'purchase_invoice_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'vat_rate' => $item['tax_rate'],
                    'total' => $itemSub + $itemTax,
                ]);

                if ($validated['status'] === 'confirmed') {
                    $wp = WarehouseProductModel::firstOrCreate(
                        ['warehouse_id' => $validated['warehouse_id'], 'product_id' => $item['product_id']],
                        ['id' => Str::uuid()->toString(), 'quantity' => 0]
                    );
                    $wp->quantity += $item['quantity'];
                    $wp->save();

                    StockMovementModel::create([
                        'id' => Str::uuid()->toString(),
                        'product_id' => $item['product_id'],
                        'warehouse_id' => $validated['warehouse_id'],
                        'type' => 'in',
                        'quantity' => $item['quantity'],
                        'reference_id' => $purchase->id,
                        'reference_type' => 'purchase_invoice',
                        'date' => now(),
                    ]);
                }
            }

            if ($validated['status'] === 'confirmed') {
                $supplier = SupplierModel::find($validated['supplier_id']);
                $owedAmount = $totalAmount - $paidAmount;
                if ($owedAmount > 0) {
                    $supplier->balance += $owedAmount;
                    $supplier->save();
                }
            }

            DB::commit();
            return $this->success($purchase->load('items'), 'Purchase invoice updated successfully', 200);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to update purchase invoice: ' . $e->getMessage(), 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        $purchase = PurchaseInvoiceModel::with(['items.product', 'supplier'])->find($id);
        if (!$purchase) { return $this->error('Purchase invoice not found', 404); }
        return $this->success($purchase, 'Purchase invoice retrieved successfully');
    }
    
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $purchase = PurchaseInvoiceModel::with('items')->find($id);
        if (!$purchase) { return $this->error('Purchase invoice not found', 404); }
        
        $validated = $request->validate([
            'status' => 'required|string|in:draft,confirmed,cancelled',
        ]);
        
        if ($purchase->status !== 'confirmed' && $validated['status'] === 'confirmed') {
            DB::beginTransaction();
            try {
                foreach ($purchase->items as $item) {
                    $wp = WarehouseProductModel::firstOrCreate(
                        ['warehouse_id' => $purchase->warehouse_id, 'product_id' => $item->product_id],
                        ['id' => Str::uuid()->toString(), 'quantity' => 0]
                    );

                    $wp->quantity += $item->quantity;
                    $wp->save();

                    StockMovementModel::create([
                        'id' => Str::uuid()->toString(),
                        'product_id' => $item->product_id,
                        'warehouse_id' => $purchase->warehouse_id,
                        'type' => 'in',
                        'quantity' => $item->quantity,
                        'reference_id' => $purchase->id,
                        'reference_type' => 'purchase_invoice',
                        'date' => now(),
                    ]);
                }
                
                $supplier = SupplierModel::find($purchase->supplier_id);
                $owedAmount = $purchase->total - $purchase->paid_amount; // assuming paid_amount exists or 0
                if ($owedAmount > 0) {
                    $supplier->balance += $owedAmount;
                    $supplier->save();
                }
                
                $purchase->update(['status' => $validated['status']]);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return $this->error('Failed to update status', 500);
            }
        } else {
            $purchase->update(['status' => $validated['status']]);
        }
        
        return $this->success($purchase, 'Purchase invoice status updated successfully');
    }
}
