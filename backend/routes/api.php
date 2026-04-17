<?php

use Illuminate\Support\Facades\Route;
use App\Presentation\Controllers\API\Auth\AuthController;
use App\Presentation\Controllers\API\Auth\UserController;
use App\Presentation\Controllers\API\Sales\InvoiceController;
use App\Presentation\Controllers\API\Inventory\ProductController;
use App\Presentation\Controllers\API\Accounting\ReportsController;
use App\Presentation\Controllers\API\CRM\CustomerController;
use App\Presentation\Controllers\API\CRM\SupplierController;
use App\Presentation\Controllers\API\Purchases\PurchaseController;
use App\Presentation\Controllers\API\Partnerships\PartnerController;
use App\Presentation\Controllers\API\Partnerships\ProfitDistributionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All tenant API routes are wrapped with tenant + subscription middleware.
| Rate limiting is applied globally.
|
*/

// Public routes (no auth needed)
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
});

// Tenant-scoped, authenticated routes
Route::middleware(['tenant', 'subscription.active', 'auth:sanctum', 'throttle:120,1'])->group(function () {

    // Auth & Users
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::apiResource('users', UserController::class);

    // Sales / POS
    Route::prefix('sales')->group(function () {
        Route::get('/invoices', [InvoiceController::class, 'index']);
        Route::post('/invoices/bulk', [InvoiceController::class, 'bulkStore']);
        Route::post('/invoices', [InvoiceController::class, 'store']);
        Route::get('/invoices/{id}', [InvoiceController::class, 'show']);
        Route::put('/invoices/{id}', [InvoiceController::class, 'update']);
        Route::put('/invoices/{id}/status', [InvoiceController::class, 'updateStatus']);
        Route::get('/reports/sales', [InvoiceController::class, 'salesReport']);
        
        // Sales Returns
        Route::get('/returns', [\App\Presentation\Controllers\API\Sales\SalesReturnController::class, 'index']);
        Route::post('/returns', [\App\Presentation\Controllers\API\Sales\SalesReturnController::class, 'store']);
        Route::get('/returns/{id}', [\App\Presentation\Controllers\API\Sales\SalesReturnController::class, 'show']);
        Route::put('/returns/{id}/status', [\App\Presentation\Controllers\API\Sales\SalesReturnController::class, 'updateStatus']);
        
        // Quotations
        Route::get('/quotations', [\App\Presentation\Controllers\API\Sales\QuotationController::class, 'index']);
        Route::post('/quotations', [\App\Presentation\Controllers\API\Sales\QuotationController::class, 'store']);
        Route::get('/quotations/{id}', [\App\Presentation\Controllers\API\Sales\QuotationController::class, 'show']);
        Route::put('/quotations/{id}', [\App\Presentation\Controllers\API\Sales\QuotationController::class, 'update']);
        Route::put('/quotations/{id}/status', [\App\Presentation\Controllers\API\Sales\QuotationController::class, 'updateStatus']);
        
        // Shipping
        Route::get('/shipping', [\App\Presentation\Controllers\API\Sales\ShippingController::class, 'index']);
        Route::post('/shipping', [\App\Presentation\Controllers\API\Sales\ShippingController::class, 'store']);
        Route::get('/shipping/{id}', [\App\Presentation\Controllers\API\Sales\ShippingController::class, 'show']);
        Route::put('/shipping/{id}', [\App\Presentation\Controllers\API\Sales\ShippingController::class, 'update']);
        Route::put('/shipping/{id}/status', [\App\Presentation\Controllers\API\Sales\ShippingController::class, 'updateStatus']);
    });

    // Treasury & Accounting
    Route::prefix('treasury')->group(function () {
        Route::get('/safes', [\App\Presentation\Controllers\API\Treasury\TreasuryController::class, 'getSafes']);
        Route::post('/safes', [\App\Presentation\Controllers\API\Treasury\TreasuryController::class, 'storeSafe']);
        Route::post('/safes/{id}/assign-user', [\App\Presentation\Controllers\API\Treasury\TreasuryController::class, 'assignUser']);
        Route::post('/transactions', [\App\Presentation\Controllers\API\Treasury\TreasuryController::class, 'storeTransaction']);
        Route::post('/transfer', [\App\Presentation\Controllers\API\Treasury\TreasuryController::class, 'transfer']);
    });

    Route::prefix('expenses')->group(function () {
        Route::get('/categories', [\App\Presentation\Controllers\API\Treasury\ExpenseController::class, 'getCategories']);
        Route::post('/categories', [\App\Presentation\Controllers\API\Treasury\ExpenseController::class, 'storeCategory']);
        Route::get('/', [\App\Presentation\Controllers\API\Treasury\ExpenseController::class, 'index']);
        Route::post('/', [\App\Presentation\Controllers\API\Treasury\ExpenseController::class, 'store']);
    });

    // HR
    Route::prefix('hr')->group(function () {
        Route::get('/employees', [\App\Presentation\Controllers\API\HR\HrController::class, 'getEmployees']);
        Route::post('/employees', [\App\Presentation\Controllers\API\HR\HrController::class, 'storeEmployee']);
        
        Route::get('/attendance', [\App\Presentation\Controllers\API\HR\HrController::class, 'getAttendances']);
        Route::post('/attendance/check-in', [\App\Presentation\Controllers\API\HR\HrController::class, 'checkIn']);
        Route::post('/attendance/check-out', [\App\Presentation\Controllers\API\HR\HrController::class, 'checkOut']);
        
        Route::get('/leaves', [\App\Presentation\Controllers\API\HR\HrController::class, 'getLeaves']);
        Route::post('/leaves', [\App\Presentation\Controllers\API\HR\HrController::class, 'applyLeave']);
        
        Route::get('/payrolls', [\App\Presentation\Controllers\API\HR\HrController::class, 'getPayrolls']);
        Route::post('/payrolls/generate', [\App\Presentation\Controllers\API\HR\HrController::class, 'generatePayroll']);
        Route::post('/payrolls/{id}/pay', [\App\Presentation\Controllers\API\HR\HrController::class, 'payPayroll']);
    });
    
    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('/pl', [\App\Presentation\Controllers\API\Reports\ReportController::class, 'getProfitAndLoss']);
        Route::get('/inventory', [\App\Presentation\Controllers\API\Reports\ReportController::class, 'getInventoryReport']);
        Route::get('/accounts', [\App\Presentation\Controllers\API\Reports\ReportController::class, 'getAccountsReport']);
        Route::get('/kpis', [\App\Presentation\Controllers\API\Reports\ReportController::class, 'getGeneralKpis']);
        Route::get('/vat-report', [\App\Presentation\Controllers\API\Reports\ReportController::class, 'getVatReport']);
    });
    
    // Inventory
    Route::prefix('inventory')->group(function () {
        // Branches & Warehouses
        Route::apiResource('branches', \App\Presentation\Controllers\API\Inventory\BranchController::class);
        Route::apiResource('warehouses', \App\Presentation\Controllers\API\Inventory\WarehouseController::class);

        // Stock Transfers
        Route::get('/stock-transfers', [\App\Presentation\Controllers\API\Inventory\StockTransferController::class, 'index']);
        Route::post('/stock-transfers', [\App\Presentation\Controllers\API\Inventory\StockTransferController::class, 'store']);
        Route::get('/stock-transfers/{id}', [\App\Presentation\Controllers\API\Inventory\StockTransferController::class, 'show']);
        Route::post('/stock-transfers/{id}/approve', [\App\Presentation\Controllers\API\Inventory\StockTransferController::class, 'approve']);
        Route::post('/stock-transfers/{id}/receive', [\App\Presentation\Controllers\API\Inventory\StockTransferController::class, 'receive']);
        Route::delete('/stock-transfers/{id}', [\App\Presentation\Controllers\API\Inventory\StockTransferController::class, 'destroy']);

        // Products
        Route::get('/products', [ProductController::class, 'index']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::get('/products/search', [ProductController::class, 'search']);
        Route::get('/products/low-stock', [ProductController::class, 'lowStock']);
        Route::get('/products/barcode/{barcode}', [ProductController::class, 'scanBarcode']);
        Route::get('/products/{id}', [ProductController::class, 'show']);
        Route::put('/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);

        // Adjustments
        Route::get('/adjustments', [\App\Presentation\Controllers\API\Inventory\AdjustmentController::class, 'index']);
        Route::post('/adjustments', [\App\Presentation\Controllers\API\Inventory\AdjustmentController::class, 'store']);
        Route::get('/adjustments/{id}', [\App\Presentation\Controllers\API\Inventory\AdjustmentController::class, 'show']);

        // Assembly (BOM)
        Route::get('/assembly/{productId}', [\App\Presentation\Controllers\API\Inventory\AssemblyController::class, 'getComponents']);
        Route::post('/assembly/{productId}', [\App\Presentation\Controllers\API\Inventory\AssemblyController::class, 'setComponents']);
        Route::post('/assemble', [\App\Presentation\Controllers\API\Inventory\AssemblyController::class, 'assemble']);
    });

    // CRM (Customers & Suppliers)
    Route::prefix('crm')->group(function () {
        Route::get('customers/export', [CustomerController::class, 'export']);
        Route::post('customers/import', [CustomerController::class, 'import']);
        Route::get('customers/{id}/statement', [CustomerController::class, 'statement']);
        Route::apiResource('customers', CustomerController::class);
        
        Route::post('vouchers', [\App\Presentation\Controllers\API\CRM\VoucherController::class, 'store']);
        Route::get('suppliers/export', [SupplierController::class, 'export']);
        Route::post('suppliers/import', [SupplierController::class, 'import']);
        Route::get('suppliers/{id}/statement', [SupplierController::class, 'statement']);
        Route::apiResource('suppliers', SupplierController::class);
    });

    // Purchases
    Route::prefix('purchases')->group(function () {
        Route::get('/invoices', [PurchaseController::class, 'index']);
        Route::post('/invoices', [PurchaseController::class, 'store']);
        Route::get('/invoices/{id}', [PurchaseController::class, 'show']);
        Route::put('/invoices/{id}', [PurchaseController::class, 'update']);
        Route::put('/invoices/{id}/status', [PurchaseController::class, 'updateStatus']);
        
        // Purchase Returns
        Route::get('/returns', [\App\Presentation\Controllers\API\Purchases\PurchaseReturnController::class, 'index']);
        Route::post('/returns', [\App\Presentation\Controllers\API\Purchases\PurchaseReturnController::class, 'store']);
        Route::get('/returns/{id}', [\App\Presentation\Controllers\API\Purchases\PurchaseReturnController::class, 'show']);
        Route::put('/returns/{id}/status', [\App\Presentation\Controllers\API\Purchases\PurchaseReturnController::class, 'updateStatus']);
    });

    // ZATCA Integration onboarding
    Route::prefix('zatca')->group(function () {
        Route::post('/onboard', [\App\Presentation\Controllers\API\Sales\ZatcaOnboardingController::class, 'submitOtp']);
        Route::get('/status', [\App\Presentation\Controllers\API\Sales\ZatcaOnboardingController::class, 'status']);
    });

    // Partnerships & Profit Distribution
    Route::prefix('partnerships')->group(function () {
        Route::apiResource('partners', PartnerController::class);
        Route::post('/partners/{id}/withdraw', [PartnerController::class, 'withdrawProfits']);
        
        Route::get('/distributions', [ProfitDistributionController::class, 'index']);
        Route::get('/distributions/preview', [ProfitDistributionController::class, 'preview']);
        Route::post('/distributions', [ProfitDistributionController::class, 'store']);
    });

    // Accounting & Reports
    Route::prefix('accounting')->group(function () {
        Route::get('/chart-of-accounts', [ReportsController::class, 'chartOfAccounts']);
        Route::get('/journal-entries', [ReportsController::class, 'journalEntries']);
        Route::get('/reports/trial-balance', [ReportsController::class, 'trialBalance']);
        Route::get('/reports/income-statement', [ReportsController::class, 'incomeStatement']);
        Route::get('/reports/balance-sheet', [ReportsController::class, 'balanceSheet']);
        Route::get('/reports/general-ledger', [ReportsController::class, 'generalLedger']);
    });

    // Settings
    Route::get('/settings', [\App\Presentation\Controllers\API\SettingsController::class, 'index']);
    Route::put('/settings', [\App\Presentation\Controllers\API\SettingsController::class, 'update']);

    // AI & Forecasting Analytics
    Route::prefix('analytics')->group(function () {
        Route::get('/inventory-forecast', [\App\Presentation\Controllers\API\Analytics\ForecastingController::class, 'getInventoryForecast']);
        Route::post('/auto-draft-po', [\App\Presentation\Controllers\API\Analytics\ForecastingController::class, 'autoDraftPurchaseOrder']);
        Route::get('/partner-forecast', [\App\Presentation\Controllers\API\Analytics\ForecastingController::class, 'getPartnerForecast']);
    });

    // Admin: Partner Portal Management
    Route::prefix('partnerships')->group(function () {
        Route::post('/partners/{id}/enable-portal', [PartnerController::class, 'enablePortal']);
        Route::post('/partners/{id}/send-magic-link', [PartnerController::class, 'sendMagicLink']);
    });
});

// ─────────────────────────────────────────────────────────────
//  Partner Portal — Separate auth, tenant-scoped via query param
// ─────────────────────────────────────────────────────────────
Route::middleware(['tenant'])->prefix('portal')->group(function () {
    // Public portal auth
    Route::post('/login', [\App\Presentation\Controllers\API\Portal\PartnerAuthController::class, 'login']);
    Route::post('/magic-link', [\App\Presentation\Controllers\API\Portal\PartnerAuthController::class, 'sendMagicLink']);
    Route::post('/magic-link/verify', [\App\Presentation\Controllers\API\Portal\PartnerAuthController::class, 'verifyMagicLink']);

    // Protected portal routes (partner token required)
    Route::middleware(['partner.auth'])->group(function () {
        Route::get('/me', [\App\Presentation\Controllers\API\Portal\PartnerAuthController::class, 'me']);
        Route::post('/logout', [\App\Presentation\Controllers\API\Portal\PartnerAuthController::class, 'logout']);
        Route::get('/dashboard', [\App\Presentation\Controllers\API\Portal\PartnerDashboardController::class, 'dashboard']);
        Route::get('/profits', [\App\Presentation\Controllers\API\Portal\PartnerDashboardController::class, 'profits']);
        Route::get('/statement', [\App\Presentation\Controllers\API\Portal\PartnerDashboardController::class, 'statement']);
        Route::get('/statement/pdf', [\App\Presentation\Controllers\API\Portal\PartnerDashboardController::class, 'exportPdf']);
        Route::get('/top-products', [\App\Presentation\Controllers\API\Portal\PartnerDashboardController::class, 'topProducts']);
        Route::get('/forecast', [\App\Presentation\Controllers\API\Portal\PartnerDashboardController::class, 'forecast']);
    });
});
