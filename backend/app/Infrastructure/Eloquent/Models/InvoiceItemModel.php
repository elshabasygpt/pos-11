<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquent\Models;

class InvoiceItemModel extends BaseModel
{
    protected $table = 'invoice_items';

    protected $fillable = [
        'invoice_id', 'product_id', 'quantity', 'unit_price', 'cost_price',
        'discount_percent', 'vat_rate', 'total',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(InvoiceModel::class, 'invoice_id');
    }

    public function product()
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }
}
