<?php

namespace Tests\Unit\Actions\Inventory;

use App\Actions\Inventory\ReceiveBatch;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReceiveBatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_receiving_a_batch_sets_received_and_remaining_to_the_same_quantity(): void
    {
        $category = Category::create(['name' => 'Feeds']);
        $user = User::factory()->manager()->create();
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Layers Mash',
            'base_unit' => 'kg',
            'buying_price_cents' => 5000,
            'selling_price_cents' => 6200,
            'reorder_level' => 50,
        ]);

        $batch = (new ReceiveBatch)(
            $product,
            'B-100',
            Carbon::now()->addMonths(6)->toDateString(),
            '250',
            5000,
            Carbon::now()->toDateString(),
            $user,
        );

        $this->assertSame('250.000', (string) $batch->quantity_received);
        $this->assertSame('250.000', (string) $batch->quantity_remaining);
        $this->assertSame('B-100', $batch->batch_number);
    }

    public function test_batch_number_is_auto_generated_when_not_supplied(): void
    {
        $category = Category::create(['name' => 'Equipment']);
        $user = User::factory()->manager()->create();
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Wheelbarrow',
            'base_unit' => 'pcs',
            'buying_price_cents' => 380000,
            'selling_price_cents' => 520000,
            'reorder_level' => 2,
        ]);

        $batch = (new ReceiveBatch)($product, null, null, '3', 380000, Carbon::now()->toDateString(), $user);

        $this->assertNotEmpty($batch->batch_number);
        $this->assertNull($batch->expiry_date);
    }

    public function test_receiving_a_batch_is_audit_logged(): void
    {
        $category = Category::create(['name' => 'Feeds']);
        $user = User::factory()->manager()->create();
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Chick Mash',
            'base_unit' => 'kg',
            'buying_price_cents' => 5500,
            'selling_price_cents' => 6800,
            'reorder_level' => 20,
        ]);

        (new ReceiveBatch)($product, 'B-1', null, '100', 5500, Carbon::now()->toDateString(), $user);

        $this->assertDatabaseHas('audit_logs', ['action' => 'batch.received']);
    }
}
