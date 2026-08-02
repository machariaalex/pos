<?php

namespace Tests\Unit\Actions\Reports;

use App\Actions\Sales\CompleteSale;
use App\Actions\Sales\ProcessReturn;
use App\Models\Batch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

abstract class ReportsActionTestCase extends TestCase
{
    use RefreshDatabase;

    protected function makeProduct(array $overrides = []): Product
    {
        $category = Category::firstOrCreate(['name' => 'Feeds']);

        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Dairy Meal',
            'base_unit' => 'kg',
            'buying_price_cents' => 5000,
            'selling_price_cents' => 6500,
            'reorder_level' => 10,
        ], $overrides));
    }

    protected function makeBatch(Product $product, User $user, array $overrides = []): Batch
    {
        return Batch::create(array_merge([
            'product_id' => $product->id,
            'batch_number' => 'B'.random_int(1000, 9999),
            'expiry_date' => Carbon::now()->addMonths(6),
            'quantity_received' => 100,
            'quantity_remaining' => 100,
            'buying_price_cents' => $product->buying_price_cents,
            'received_at' => Carbon::now(),
            'created_by' => $user->id,
        ], $overrides));
    }

    protected function makeCustomer(array $overrides = []): Customer
    {
        return Customer::create(array_merge([
            'name' => 'Test Farmer',
            'phone' => '0700000000',
            'credit_limit_cents' => 1000000,
            'balance_cents' => 0,
        ], $overrides));
    }

    protected function completeSale(array $lines, ?Customer $customer, array $payments, User $cashier, ?Carbon $on = null): Sale
    {
        $sale = app(CompleteSale::class)($lines, $customer, $payments, $cashier);

        if ($on) {
            $sale->completed_at = $on;
            $sale->created_at = $on;
            $sale->saveQuietly();
        }

        return $sale->fresh();
    }

    protected function processReturn(Sale $sale, array $returnLines, string $reason, User $approver, User $processedBy, ?Carbon $on = null): SaleReturn
    {
        $return = app(ProcessReturn::class)($sale, $returnLines, $reason, $approver, $processedBy);

        if ($on) {
            $return->created_at = $on;
            $return->saveQuietly();
        }

        return $return->fresh();
    }
}
