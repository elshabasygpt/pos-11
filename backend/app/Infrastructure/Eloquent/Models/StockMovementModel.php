<?php

namespace App\Infrastructure\Eloquent\Models;

class StockMovementModel extends BaseModel
{
    protected $table = 'stock_movements';
    protected $fillable = ['product_id', 'warehouse_id', 'type', 'quantity', 'cost_per_unit', 'reference_type', 'reference_id', 'notes', 'created_by'];
    protected $casts = ['quantity' => 'decimal:2', 'cost_per_unit' => 'decimal:2'];
}
