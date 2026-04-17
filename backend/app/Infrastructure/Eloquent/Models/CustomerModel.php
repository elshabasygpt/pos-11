<?php

namespace App\Infrastructure\Eloquent\Models;

class CustomerModel extends BaseModel
{
    protected $table = 'customers';
    protected $fillable = ['name', 'email', 'phone', 'address', 'tax_number', 'balance', 'is_active', 'created_by', 'updated_by'];
    protected $casts = ['balance' => 'decimal:2', 'is_active' => 'boolean'];
    public function invoices() { return $this->hasMany(InvoiceModel::class, 'customer_id'); }
    public function salesReturns() { return $this->hasMany(SalesReturnModel::class, 'customer_id'); }
    public function quotations() { return $this->hasMany(QuotationModel::class, 'customer_id'); }
    public function vouchers() { return $this->hasMany(VoucherModel::class, 'customer_id'); }
}
