<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquent\Models;

use App\Infrastructure\Eloquent\Traits\BranchScoped;

class InvoiceModel extends BaseModel
{
    use BranchScoped;

    protected $table = 'invoices';

    protected $fillable = [
        'invoice_number', 'customer_id', 'type', 'subtotal',
        'vat_amount', 'discount_amount', 'total', 'status',
        'notes', 'warehouse_id', 'branch_id', 'invoice_date',
        'created_by', 'updated_by',
        'zatca_qr_code', 'zatca_xml', 'zatca_hash', 
        'zatca_uuid', 'zatca_status', 'zatca_error_message',
        'commission_amount'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'invoice_date' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(InvoiceItemModel::class, 'invoice_id');
    }

    public function customer() { return $this->belongsTo(CustomerModel::class, 'customer_id'); }
    public function shippingInvoices() { return $this->hasMany(ShippingInvoiceModel::class, 'invoice_id'); }
    public function salesReturns() { return $this->hasMany(SalesReturnModel::class, 'invoice_id'); }

    public function warehouse()
    {
        return $this->belongsTo(WarehouseModel::class, 'warehouse_id');
    }

    public function branch()
    {
        return $this->belongsTo(BranchModel::class, 'branch_id');
    }

    public function creator()
    {
        return $this->belongsTo(UserModel::class, 'created_by');
    }
}
