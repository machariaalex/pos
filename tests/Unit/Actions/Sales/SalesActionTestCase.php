<?php

namespace Tests\Unit\Actions\Sales;

use App\Models\Batch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

abstract class SalesActionTestCase extends TestCase
{
    use RefreshDatabase;

    protected function makeProduct(array $overrides = []): Product
    {
        $category = Category::firstOrCreate(['name' => 'Feeds']);

        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Dairy Meal',
            'base_unit' => 'kg',
            'buying_price_cents' => 5200,
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
            'quantity_received' => 50,
            'quantity_remaining' => 50,
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
}
