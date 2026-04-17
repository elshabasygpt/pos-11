<?php

declare(strict_types=1);

namespace App\Presentation\Controllers\API\Inventory;

use App\Presentation\Controllers\API\BaseController;
use App\Domain\Inventory\Repositories\ProductRepositoryInterface;
use App\Infrastructure\Eloquent\Models\ProductModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends BaseController
{
    public function __construct(private ProductRepositoryInterface $productRepository) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'is_active']);
        return $this->paginated($this->productRepository->paginate((int)$request->get('per_page', 15), $filters));
    }

    public function show(string $id): JsonResponse
    {
        $product = $this->productRepository->findById($id);
        return $product ? $this->success($product->toArray()) : $this->error('Product not found.', 404);
    }

    public function search(Request $request): JsonResponse
    {
        $results = $this->productRepository->search($request->get('q', ''));
        return $this->success($results);
    }

    public function lowStock(Request $request): JsonResponse
    {
        $warehouseId = $request->get('warehouse_id');
        if (!$warehouseId) return $this->error('warehouse_id is required.', 422);
        return $this->success($this->productRepository->getLowStockProducts($warehouseId));
    }

    public function scanBarcode(string $barcode): JsonResponse
    {
        $product = $this->productRepository->findByBarcode($barcode);
        return $product ? $this->success($product->toArray()) : $this->error('Product not found.', 404);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sku' => 'required|string|unique:products,sku',
            'barcode' => 'nullable|string|unique:products,barcode',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'selling_price' => 'required|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        $validated['id'] = Str::uuid()->toString();
        $validated['is_active'] = $validated['is_active'] ?? true;
        
        $product = ProductModel::create($validated);
        
        return $this->success($product, 'Product created successfully', 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $product = ProductModel::find($id);

        if (!$product) {
            return $this->error('Product not found', 404);
        }

        $validated = $request->validate([
            'sku' => 'sometimes|required|string|unique:products,sku,' . $id,
            'barcode' => 'nullable|string|unique:products,barcode,' . $id,
            'name' => 'sometimes|required|string',
            'description' => 'nullable|string',
            'selling_price' => 'sometimes|required|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        $product->update($validated);
        
        return $this->success($product, 'Product updated successfully');
    }

    public function destroy(string $id): JsonResponse
    {
        $product = ProductModel::find($id);

        if (!$product) {
            return $this->error('Product not found', 404);
        }

        // Add proper checks here if product is linked to invoices
        $product->delete();
        
        return $this->success(null, 'Product deleted successfully');
    }
}
