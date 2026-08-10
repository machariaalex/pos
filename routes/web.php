<?php

use App\Livewire\Admin\AuditLog\Index as AuditLogIndex;
use App\Livewire\Admin\Users\Index as UsersIndex;
use App\Livewire\Auth\Login;
use App\Livewire\CashUp\Index as CashUpIndex;
use App\Livewire\Customers\DebtorsReport;
use App\Livewire\Customers\Index as CustomersIndex;
use App\Livewire\Customers\Show as CustomerShow;
use App\Livewire\Dashboard;
use App\Livewire\Expenses\Categories\Index as ExpenseCategoriesIndex;
use App\Livewire\Expenses\Index as ExpensesIndex;
use App\Livewire\Inventory\Categories\Index as CategoriesIndex;
use App\Livewire\Inventory\Products\Index as ProductsIndex;
use App\Livewire\Inventory\Products\Show as ProductShow;
use App\Livewire\Inventory\ReceiveStock\Index as ReceiveStockIndex;
use App\Livewire\Inventory\StockTakes\Index as StockTakesIndex;
use App\Livewire\Inventory\StockTakes\Show as StockTakeShow;
use App\Livewire\Reports\ExpiryReport;
use App\Livewire\Reports\FastSlowMovers;
use App\Livewire\Reports\Index as ReportsIndex;
use App\Livewire\Reports\ProfitReport;
use App\Livewire\Reports\SalesReport;
use App\Livewire\Reports\StockValuation;
use App\Livewire\Sales\Pos;
use App\Livewire\Sales\Receipt;
use App\Livewire\Sales\Returns as SalesReturns;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::middleware(['auth', 'active'])->group(function () {
    Route::redirect('/', '/dashboard');
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/users', UsersIndex::class)->name('users.index');

    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/products', ProductsIndex::class)->name('products.index');
        Route::get('/products/{product}', ProductShow::class)->name('products.show');
        Route::get('/categories', CategoriesIndex::class)->name('categories.index');
        Route::get('/receive-stock', ReceiveStockIndex::class)->name('receive-stock');
        Route::get('/stock-takes', StockTakesIndex::class)->name('stock-takes.index');
        Route::get('/stock-takes/{stockTake}', StockTakeShow::class)->name('stock-takes.show');
    });

    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/', Pos::class)->name('pos');
        Route::get('/{sale}', Receipt::class)->name('receipt');
        Route::get('/{sale}/returns', SalesReturns::class)->name('returns');
    });

    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', CustomersIndex::class)->name('index');
        Route::get('/debtors', DebtorsReport::class)->name('debtors');
        Route::get('/{customer}', CustomerShow::class)->name('show');
    });

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', ReportsIndex::class)->name('index');
        Route::get('/sales', SalesReport::class)->name('sales');
        Route::get('/profit', ProfitReport::class)->name('profit');
        Route::get('/stock-valuation', StockValuation::class)->name('stock-valuation');
        Route::get('/fast-slow-movers', FastSlowMovers::class)->name('fast-slow-movers');
        Route::get('/expiry', ExpiryReport::class)->name('expiry');
    });

    Route::prefix('expenses')->name('expenses.')->group(function () {
        Route::get('/', ExpensesIndex::class)->name('index');
        Route::get('/categories', ExpenseCategoriesIndex::class)->name('categories.index');
    });

    Route::get('/cash-up', CashUpIndex::class)->name('cash-up.index');
    Route::get('/audit-log', AuditLogIndex::class)->name('audit-log.index');
});
