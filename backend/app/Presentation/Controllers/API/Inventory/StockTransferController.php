<?php

declare(strict_types=1);

namespace App\Presentation\Controllers\API\Inventory;

use App\Infrastructure\Eloquent\Models\StockTransferModel;
use App\Domain\Inventory\Services\StockTransferService;
use App\Presentation\Controllers\API\BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StockTransferController extends BaseController
{
    public function __construct(
        private StockTransferService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = StockTransferModel::with(['fromWarehouse', 'toWarehouse', 'items.product']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $transfers = $query->latest()->paginate($request->per_page ?? 15);

        return $this->success(['transfers' => $transfers]);
    }

    public function show(string $id): JsonResponse
    {
        $transfer = StockTransferModel::with(['fromWarehouse', 'toWarehouse', 'items.product'])->findOrFail($id);
        return $this->success(['transfer' => $transfer]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from_warehouse_id' => 'required|uuid|exists:tenant.warehouses,id',
            'to_warehouse_id' => 'required|uuid|exists:tenant.warehouses,id|different:from_warehouse_id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid|exists:tenant.products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $userId = $request->user()?->id ?? '';

        try {
            $transfer = $this->service->createTransfer($data, $userId);
            return $this->success(['transfer' => $transfer], 'Stock transfer created as draft.', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        $userId = $request->user()?->id ?? '';

        try {
            $transfer = $this->service->approveTransfer($id, $userId);
            return $this->success(['transfer' => $transfer], 'Stock transfer approved and inventory deduced.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function receive(Request $request, string $id): JsonResponse
    {
        $userId = $request->user()?->id ?? '';
        
        $data = $request->validate([
            'items' => 'sometimes|array',
            'items.*.id' => 'required_with:items|uuid',
            'items.*.received_quantity' => 'required_with:items|numeric|min:0',
        ]);

        try {
            $transfer = $this->service->receiveTransfer($id, $userId, $data['items'] ?? []);
            return $this->success(['transfer' => $transfer], 'Stock transfer received and inventory added.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        $transfer = StockTransferModel::findOrFail($id);

        if ($transfer->status !== 'draft') {
            return $this->error('Only draft transfers can be deleted. Cancel it instead.', 400);
        }

        $transfer->delete();
        return $this->success(null, 'Transfer deleted completely.');
    }
}
