<?php

declare(strict_types=1);

namespace App\Presentation\Controllers\API\Sales;

use App\Presentation\Controllers\API\BaseController;
use App\Infrastructure\Eloquent\Models\SalesReturnModel;
use App\Infrastructure\Eloquent\Models\SalesReturnItemModel;
use App\Infrastructure\Eloquent\Models\ProductModel;
use App\Infrastructure\Eloquent\Models\StockMovementModel;
use App\Infrastructure\Eloquent\Models\CustomerModel;
use App\Infrastructure\Eloquent\Models\WarehouseProductModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SalesReturnController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $limit = $request->query('limit', '15');
        $status = $request->query('status');

        $query = SalesReturnModel::with(['customer', 'invoice', 'items.product', 'creator'])->orderBy('return_date', 'desc');
        
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $returns = $query->paginate((int) $limit);

        return $this->paginated($returns->toArray(), 'Sales returns retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|uuid|exists:customers,id',
            'warehouse_id' => 'nullable|uuid|exists:warehouses,id',
            'invoice_id' => 'nullable|uuid|exists:invoices,id',
            'return_date' => 'nullable|date',
            'status' => 'required|string|in:draft,completed,cancelled',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.vat_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            DB::beginTransaction();

            $subtotalAmount = 0;
            $taxAmount = 0;
            $totalProfit = 0;

            foreach ($validated['items'] as $item) {
                $product = ProductModel::find($item['product_id']);
                $costPrice = (float) ($product->cost_price ?? 0);
                
                $gross = $item['quantity'] * $item['unit_price'];
                $vatRate = $item['vat_rate'] ?? 15;
                $itemTax = $gross * ($vatRate / 100);
                $subtotalAmount += $gross;
                $taxAmount += $itemTax;

                // Profit on returned items (to be reversed)
                $totalProfit += ($item['unit_price'] - $costPrice) * $item['quantity'];
            }
            $totalAmount = $subtotalAmount + $taxAmount;

            // Calculate Commission reversal (negative)
            $currentUser = auth()->user();
            $commissionRate = (float) ($currentUser->commission_rate ?? 0);
            $commissionAmount = - ($totalProfit * ($commissionRate / 100));

            $lastReturn = SalesReturnModel::latest('created_at')->first();
            $nextNum = $lastReturn ? ((int) str_replace('RET-', '', $lastReturn->return_number)) + 1 : 1;
            $returnNumber = 'RET-' . str_pad((string)$nextNum, 6, '0', STR_PAD_LEFT);

            $salesReturn = SalesReturnModel::create([
                'id' => Str::uuid()->toString(),
                'return_number' => $returnNumber,
                'invoice_id' => $validated['invoice_id'] ?? null,
                'customer_id' => $validated['customer_id'] ?? null,
                'warehouse_id' => $validated['warehouse_id'] ?? null,
                'return_date' => $validated['return_date'] ?? now(),
                'subtotal' => $subtotalAmount,
                'vat_amount' => $taxAmount,
                'total' => $totalAmount,
                'commission_amount' => $commissionAmount,
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $product = ProductModel::find($item['product_id']);
                $costPrice = (float) ($product->cost_price ?? 0);

                $gross = $item['quantity'] * $item['unit_price'];
                $vatRate = $item['vat_rate'] ?? 15;
                $itemTax = $gross * ($vatRate / 100);

                SalesReturnItemModel::create([
                    'id' => Str::uuid()->toString(),
                    'sales_return_id' => $salesReturn->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'cost_price' => $costPrice,
                    'vat_rate' => $vatRate,
                    'total' => $gross + $itemTax,
                ]);

                if ($validated['status'] === 'completed' && !empty($validated['warehouse_id'])) {
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
                        'reference_id' => $salesReturn->id,
                        'reference_type' => 'sales_return',
                        'date' => now(),
                    ]);
                }
            }

            if ($validated['status'] === 'completed' && !empty($validated['customer_id'])) {
                $customer = CustomerModel::find($validated['customer_id']);
                if ($customer) {
                    $customer->balance -= $totalAmount;
                    $customer->save();
                }
            }

            DB::commit();

            return $this->success($salesReturn->load('items'), 'Sales return created successfully', 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create sales return: ' . $e->getMessage(), 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        $salesReturn = SalesReturnModel::with(['items.product', 'customer', 'invoice'])->find($id);

        if (!$salesReturn) {
            return $this->error('Sales return not found', 404);
        }

        return $this->success($salesReturn, 'Sales return retrieved successfully');
    }
    
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $salesReturn = SalesReturnModel::with('items')->find($id);

        if (!$salesReturn) {
            return $this->error('Sales return not found', 404);
        }
        
        $validated = $request->validate([
            'status' => 'required|string|in:draft,completed,cancelled',
        ]);
        
        if ($salesReturn->status !== 'completed' && $validated['status'] === 'completed') {
            DB::beginTransaction();
            try {
                // Receive items back into stock
                foreach ($salesReturn->items as $item) {
                    $wp = WarehouseProductModel::firstOrCreate(
                        ['warehouse_id' => $salesReturn->warehouse_id, 'product_id' => $item->product_id],
                        ['id' => Str::uuid()->toString(), 'quantity' => 0]
                    );

                    $wp->quantity += $item->quantity;
                    $wp->save();

                    StockMovementModel::create([
                        'id' => Str::uuid()->toString(),
                        'product_id' => $item->product_id,
                        'warehouse_id' => $salesReturn->warehouse_id,
                        'type' => 'in',
                        'quantity' => $item->quantity,
                        'reference_id' => $salesReturn->id,
                        'reference_type' => 'sales_return',
                        'date' => now(),
                    ]);
                }
                
                $customer = CustomerModel::find($salesReturn->customer_id);
                $customer->balance -= $salesReturn->total;
                $customer->save();
                
                $salesReturn->update(['status' => $validated['status']]);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return $this->error('Failed to update status', 500);
            }
        } else {
            $salesReturn->update(['status' => $validated['status']]);
        }
        
        return $this->success($salesReturn, 'Sales return status updated successfully');
    }
}
