<?php

namespace Tests\Unit\Actions\Reports;

use App\Actions\Reports\ComputeStockValuation;
use App\Models\Category;
use App\Models\User;

class ComputeStockValuationTest extends ReportsActionTestCase
{
    public function test_single_batch_valuation(): void
    {
        $user = User::factory()->owner()->create();
        $product = $this->makeProduct(['buying_price_cents' => 5000]);
        $this->makeBatch($product, $user, ['quantity_remaining' => 30]);

        $valuation = (new ComputeStockValuation)();

        $this->assertSame(150000, $valuation['total_cents']); // 30 * 5000
    }

    public function test_batches_of_the_same_product_can_have_different_costs(): void
    {
        $user = User::factory()->owner()->create();
        $product = $this->makeProduct(['buying_price_cents' => 5000]);
        $this->makeBatch($product, $user, ['quantity_remaining' => 20, 'buying_price_cents' => 5000]);
        $this->makeBatch($product, $user, ['quantity_remaining' => 10, 'buying_price_cents' => 5500]);

        $valuation = (new ComputeStockValuation)();

        $this->assertSame(155000, $valuation['total_cents']); // 20*5000 + 10*5500
    }

    public function test_zero_stock_batches_are_excluded(): void
    {
        $user = User::factory()->owner()->create();
        $product = $this->makeProduct(['buying_price_cents' => 5000]);
        $this->makeBatch($product, $user, ['quantity_remaining' => 0]);

        $valuation = (new ComputeStockValuation)();

        $this->assertSame(0, $valuation['total_cents']);
    }

    public function test_valuation_is_grouped_by_category(): void
    {
        $user = User::factory()->owner()->create();
        $feeds = Category::firstOrCreate(['name' => 'Feeds']);
        $medicines = Category::firstOrCreate(['name' => 'Vet Medicines']);

        $feedProduct = $this->makeProduct(['category_id' => $feeds->id, 'buying_price_cents' => 5000]);
        $this->makeBatch($feedProduct, $user, ['quantity_remaining' => 10]);

        $medProduct = $this->makeProduct(['category_id' => $medicines->id, 'name' => 'Dewormer', 'buying_price_cents' => 2000]);
        $this->makeBatch($medProduct, $user, ['quantity_remaining' => 5]);

        $valuation = (new ComputeStockValuation)();

        $this->assertSame(50000, $valuation['by_category']['Feeds']);
        $this->assertSame(10000, $valuation['by_category']['Vet Medicines']);
        $this->assertSame(60000, $valuation['total_cents']);
    }
}
