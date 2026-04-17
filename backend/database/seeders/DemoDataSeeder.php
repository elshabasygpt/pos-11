<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed comprehensive demo data for showcasing the system.
     * Run: php artisan db:seed --class=DemoDataSeeder
     */
    public function run(): void
    {
        // ── Customers ──────────────────────────────────
        $customers = [
            ['name' => 'شركة الأمانة للتجارة', 'email' => 'amanah@company.sa', 'phone' => '+966501111111', 'address' => 'الرياض - حي العليا', 'tax_number' => '300001111100003'],
            ['name' => 'مؤسسة النور للمقاولات', 'email' => 'noor@construct.sa', 'phone' => '+966502222222', 'address' => 'جدة - شارع التحلية', 'tax_number' => '300002222200003'],
            ['name' => 'Al-Farida Trading Co.', 'email' => 'info@alfarida.com', 'phone' => '+966503333333', 'address' => 'الدمام - حي الفيصلية', 'tax_number' => '300003333300003'],
            ['name' => 'مجموعة الخليج الصناعية', 'email' => 'gulf@industrial.sa', 'phone' => '+966504444444', 'address' => 'الرياض - المنطقة الصناعية', 'tax_number' => '300004444400003'],
            ['name' => 'Star Solutions LLC', 'email' => 'hr@starsol.com', 'phone' => '+966505555555', 'address' => 'أبها - حي المروج', 'tax_number' => '300005555500003'],
        ];
        foreach ($customers as $c) {
            DB::connection('tenant')->table('customers')->insert(
                array_merge($c, ['id' => Uuid::uuid4()->toString(), 'balance' => 0, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()])
            );
        }
        $this->command->info('✅ ' . count($customers) . ' customers created');

        // ── Suppliers ──────────────────────────────────
        $suppliers = [
            ['name' => 'شركة الصفوة للتوريدات', 'email' => 'safwa@supply.sa', 'phone' => '+966511111111', 'address' => 'الرياض - حي الروضة', 'tax_number' => '310001111100003'],
            ['name' => 'Global Electronics KSA', 'email' => 'sales@globalelec.sa', 'phone' => '+966512222222', 'address' => 'جدة - حي البغدادية', 'tax_number' => '310002222200003'],
            ['name' => 'مؤسسة الحكمة للأجهزة', 'email' => 'hikma@devices.sa', 'phone' => '+966513333333', 'address' => 'الدمام - حي الخبر', 'tax_number' => '310003333300003'],
        ];
        foreach ($suppliers as $s) {
            DB::connection('tenant')->table('suppliers')->insert(
                array_merge($s, ['id' => Uuid::uuid4()->toString(), 'balance' => 0, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()])
            );
        }
        $this->command->info('✅ ' . count($suppliers) . ' suppliers created');

        // ── Warehouse ──────────────────────────────────
        $warehouseId = Uuid::uuid4()->toString();
        DB::connection('tenant')->table('warehouses')->insert([
            'id' => $warehouseId, 'name' => 'المستودع الرئيسي',
            'location' => 'الرياض - حي الصناعية',
            'is_default' => true, 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->command->info('✅ Main warehouse created');

        // ── Products ──────────────────────────────────
        $products = [
            ['name' => 'لابتوب Dell Inspiron 15', 'name_ar' => 'لابتوب ديل انسبايرون ١٥', 'sku' => 'LAP-001', 'barcode' => 'DELL001', 'cost_price' => 2800, 'sell_price' => 3500, 'stock' => 25],
            ['name' => 'طابعة HP LaserJet Pro',  'name_ar' => 'طابعة اتش بي ليزرجيت برو', 'sku' => 'PRT-001', 'barcode' => 'HPLJ001', 'cost_price' => 900,  'sell_price' => 1200, 'stock' => 15],
            ['name' => 'Coffee Latte',           'name_ar' => 'قهوة لاتيه بريميوم',      'sku' => 'CFE-001', 'barcode' => 'COFFEE01', 'cost_price' => 8,    'sell_price' => 18,   'stock' => 500, 'image_url' => '/images/products/coffee.png', 'is_favorite' => true],
            ['name' => 'Butter Croissant',       'name_ar' => 'كرواسون زبدة فرنسي',     'sku' => 'CRN-001', 'barcode' => 'CRSN01',  'cost_price' => 5,    'sell_price' => 12,   'stock' => 80,  'image_url' => '/images/products/croissant.png', 'is_favorite' => true],
            ['name' => 'Samsung 27 Monitor',     'name_ar' => 'شاشة سامسونج ٢٧ بوصة',   'sku' => 'MON-001', 'barcode' => 'SAM001',  'cost_price' => 700,  'sell_price' => 950,  'stock' => 30],
            ['name' => 'ورق طباعة A4 (رزمة)',   'name_ar' => 'ورق طباعة أيه أربعة',    'sku' => 'PPR-001', 'barcode' => 'PPRA4001','cost_price' => 18,   'sell_price' => 28,   'stock' => 200, 'is_favorite' => true],
            ['name' => 'أقلام ماركر متنوعة',    'name_ar' => 'أقلام ماركر متنوعة',     'sku' => 'MRK-001', 'barcode' => 'MRK001',  'cost_price' => 12,   'sell_price' => 22,   'stock' => 100],
            ['name' => 'Bosch Electric Drill',   'name_ar' => 'مثقاب كهربائي بوش',     'sku' => 'DRL-001', 'barcode' => 'BSH001',  'cost_price' => 380,  'sell_price' => 520,  'stock' => 20],
            ['name' => 'Stanley Wrench Set',     'name_ar' => 'طقم مفاتيح ستانلي',     'sku' => 'WRN-001', 'barcode' => 'STN001',  'cost_price' => 145,  'sell_price' => 210,  'stock' => 35],
        ];

        foreach ($products as $p) {
            $pid = Uuid::uuid4()->toString();
            DB::connection('tenant')->table('products')->insert([
                'id' => $pid, 'name' => $p['name'], 'name_ar' => $p['name_ar'],
                'sku' => $p['sku'], 'barcode' => $p['barcode'],
                'cost_price' => $p['cost_price'], 'sell_price' => $p['sell_price'],
                'vat_rate' => 15, 'stock_alert_level' => 5,
                'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::connection('tenant')->table('warehouse_products')->insert([
                'id' => Uuid::uuid4()->toString(),
                'warehouse_id' => $warehouseId, 'product_id' => $pid,
                'quantity' => $p['stock'], 'average_cost' => $p['cost_price'],
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $this->command->info('✅ ' . count($products) . ' products with stock created');

        // ── Partners ──────────────────────────────────
        $partners = [
            ['name' => 'محمد عبدالله الأحمري', 'phone' => '+966555000001', 'capital_amount' => 200000, 'profit_share_percentage' => 40, 'total_pending' => 18500, 'total_withdrawn' => 45000],
            ['name' => 'فيصل سعد القحطاني',    'phone' => '+966555000002', 'capital_amount' => 150000, 'profit_share_percentage' => 30, 'total_pending' => 12000, 'total_withdrawn' => 32000],
            ['name' => 'Abdullah Al-Rasheed',   'phone' => '+966555000003', 'capital_amount' => 100000, 'profit_share_percentage' => 30, 'total_pending' => 9500,  'total_withdrawn' => 22500],
        ];

        // Create a fake profit distribution round first
        $distributionId = Uuid::uuid4()->toString();
        DB::connection('tenant')->table('profit_distributions')->insert([
            'id' => $distributionId,
            'period_start' => now()->subMonths(3)->toDateString(),
            'period_end' => now()->subMonths(1)->toDateString(),
            'total_revenue' => 500000,
            'total_expenses' => 300000,
            'net_profit' => 200000,
            'distributed_amount' => 150000,
            'status' => 'approved',
            'notes' => 'توزيع أرباح الربع الأخير',
            'created_at' => now()->subDays(15),
            'updated_at' => now()->subDays(15),
        ]);

        foreach ($partners as $p) {
            $partnerId = Uuid::uuid4()->toString();
            DB::connection('tenant')->table('partners')->insert(
                array_merge($p, ['id' => $partnerId, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()])
            );

            // Give them a profit share
            DB::connection('tenant')->table('partner_profit_shares')->insert([
                'id' => Uuid::uuid4()->toString(),
                'partner_id' => $partnerId,
                'distribution_id' => $distributionId,
                'amount' => 150000 * ($p['profit_share_percentage'] / 100),
                'share_percentage' => $p['profit_share_percentage'],
                'created_at' => now()->subDays(15),
                'updated_at' => now()->subDays(15),
            ]);

            // Give them a withdrawal
            DB::connection('tenant')->table('partner_withdrawals')->insert([
                'id' => Uuid::uuid4()->toString(),
                'partner_id' => $partnerId,
                'amount' => $p['total_withdrawn'],
                'notes' => 'سحب أرباح دورية',
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ]);
        }
        $this->command->info('✅ ' . count($partners) . ' partners created with profit shares and withdrawals');

        $this->command->newLine();
        $this->command->info('🎉 Demo data seeded successfully!');
        $this->command->info('   System is ready for demonstration.');
    }
}
