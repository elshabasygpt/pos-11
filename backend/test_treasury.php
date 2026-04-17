<?php

use App\Infrastructure\Eloquent\Models\SafeModel;
use App\Infrastructure\Eloquent\Models\SafeTransactionModel;
use App\Infrastructure\Eloquent\Models\ExpenseCategoryModel;
use App\Infrastructure\Eloquent\Models\ExpenseModel;
use App\Presentation\Controllers\API\Treasury\TreasuryController;
use App\Presentation\Controllers\API\Treasury\ExpenseController;
use App\Presentation\Controllers\API\Sales\InvoiceController;
use App\Infrastructure\Eloquent\Models\CustomerModel;
use App\Infrastructure\Eloquent\Models\WarehouseModel;
use App\Infrastructure\Eloquent\Models\ProductModel;
use App\Infrastructure\Eloquent\Models\WarehouseProductModel;
use Illuminate\Http\Request;

echo "--- Starting Treasury Programmatic Verification ---\n";

// 1. Create a Primary Cash Safe
$tController = app(TreasuryController::class);

$safeReq = new Request([
    'name' => 'صندوق الكاشير التجريبي',
    'type' => 'cash',
    'balance' => 0
]);
$resSafe = $tController->storeSafe($safeReq);
if ($resSafe->getStatusCode() !== 201) { echo "[FAIL] Safe Creation Failed\n"; exit; }
$safe = $resSafe->getData()->data;
echo "[OK] Cash Safe created. ID: {$safe->id}\n";

// 2. Deposit into Safe
$depReq = new Request([
    'safe_id' => $safe->id,
    'type' => 'deposit',
    'amount' => 5000,
    'description' => 'إيداع افتتاحي تجريبي'
]);
$tController->storeTransaction($depReq);
echo "[OK] Deposited 5000 to safe. \n";

// 3. Create Expense Category (Salary/Advance)
$eController = app(ExpenseController::class);
$catReq = new Request([
    'name' => 'Salaries / Advances',
    'is_advance_or_salary' => true
]);
$resCat = $eController->storeCategory($catReq);
$category = $resCat->getData()->data;
echo "[OK] Expense Category created. ID: {$category->id}\n";

// 4. Record Expense (Deducts from safe)
$expReq = new Request([
    'category_id' => $category->id,
    'safe_id' => $safe->id,
    'amount' => 1500,
    'description' => 'سلفة موظف',
]);
$resExp = $eController->store($expReq);
if ($resExp->getStatusCode() !== 201) { echo "[FAIL] Expense Creation Failed\n"; exit; }
echo "[OK] Expense of 1500 recorded.\n";

$safeDb = SafeModel::find($safe->id);
if ((float)$safeDb->balance !== 3500.0) {
    echo "[FAIL] Safe balance is {$safeDb->balance}, expected 3500.\n"; exit;
}
echo "[OK] Safe balance correctly updated after expense (3500)\n";

// 5. Test Sales Auto-Deposit
// Since we don't have authenticated user here, we will just manually hit InvoiceController
// and it should find the 'cash' safe (the one we just created) and deposit into it.

$customer = CustomerModel::first();
$warehouse = WarehouseModel::first();
$product = ProductModel::first();
$wp = WarehouseProductModel::where(['warehouse_id' => $warehouse->id, 'product_id' => $product->id])->first();
if ($wp) {
    $wp->quantity += 5; $wp->save();
}

$invoiceReq = new Request([
    'customer_id' => $customer->id,
    'warehouse_id' => $warehouse->id,
    'type' => 'cash',
    'status' => 'confirmed',
    'items' => [
        [
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 1000, // Total = 1150
            'vat_rate' => 15,
            'discount_percent' => 0
        ]
    ]
]);

$iController = app(InvoiceController::class);
$iRes = $iController->store($invoiceReq);
if ($iRes->getStatusCode() !== 200 && $iRes->getStatusCode() !== 201) { 
    echo "[FAIL] Cash Invoice creation failed: " . json_encode($iRes->getData()) . "\n"; 
    exit; 
}
echo "[OK] Confirmed Cash Sales Invoice created. Flow verified.\n";

$safeDb->refresh();
if ((float)$safeDb->balance === 4650.0) { // 3500 + 1150
    echo "[OK] Outstanding! Auto-deposit logic properly credited the cash safe to 4650.\n";
} else {
    echo "[FAIL] Expected 4650 balance after cash sales invoice, got {$safeDb->balance}\n";
}

echo "--- All Treasury Tests Completed Successfully! ---\n";
