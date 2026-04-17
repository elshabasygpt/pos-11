<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add indexes to critical tables for performance
        Schema::connection('tenant')->table('products', function (Blueprint $table) {
            $table->index('sku');
            $table->index('barcode');
        });

        Schema::connection('tenant')->table('invoices', function (Blueprint $table) {
            $table->index('invoice_number');
            $table->index('customer_id');
            $table->index('invoice_date');
        });

        Schema::connection('tenant')->table('purchase_invoices', function (Blueprint $table) {
            $table->index('invoice_number');
            $table->index('supplier_id');
            $table->index('invoice_date');
        });

        Schema::connection('tenant')->table('customers', function (Blueprint $table) {
            $table->index('phone');
        });

        Schema::connection('tenant')->table('safe_transactions', function (Blueprint $table) {
            $table->index('transaction_date');
            $table->index('safe_id');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('products', function (Blueprint $table) {
            $table->dropIndex(['sku']);
            $table->dropIndex(['barcode']);
        });

        Schema::connection('tenant')->table('invoices', function (Blueprint $table) {
            $table->dropIndex(['invoice_number']);
            $table->dropIndex(['customer_id']);
            $table->dropIndex(['invoice_date']);
        });

        Schema::connection('tenant')->table('purchase_invoices', function (Blueprint $table) {
            $table->dropIndex(['invoice_number']);
            $table->dropIndex(['supplier_id']);
            $table->dropIndex(['invoice_date']);
        });

        Schema::connection('tenant')->table('customers', function (Blueprint $table) {
            $table->dropIndex(['phone']);
        });

        Schema::connection('tenant')->table('safe_transactions', function (Blueprint $table) {
            $table->dropIndex(['transaction_date']);
            $table->dropIndex(['safe_id']);
        });
    }
};
