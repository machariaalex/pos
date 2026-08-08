<?php

namespace Tests\Unit;

use App\Models\Batch;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UnitQuantityTest extends TestCase
{
    use RefreshDatabase;

    public function test_fractional_units_allow_fractional_quantity(): void
    {
        $category = Category::create(['name' => 'Feeds']);

        foreach (['kg', 'g', 'l', 'ml'] as $unit) {
            $product = Product::create([
                'category_id' => $category->id,
                'name' => "Product ({$unit})",
                'base_unit' => $unit,
                'buying_price_cents' => 100,
                'selling_price_cents' => 150,
                'reorder_level' => 0,
            ]);

            $this->assertTrue($product->allowsFractionalQuantity(), "{$unit} should allow fractional quantity");
        }
    }

    public function test_pieces_do_not_allow_fractional_quantity(): void
    {
        $category = Category::create(['name' => 'Equipment']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Sprayer',
            'base_unit' => 'pcs',
            'buying_price_cents' => 100,
            'selling_price_cents' => 150,
            'reorder_level' => 0,
        ]);

        $this->assertFalse($product->allowsFractionalQuantity());
    }

    public function test_selling_a_fraction_of_a_bag_leaves_correct_remaining_stock(): void
    {
        // A 50kg fertiliser bag repackaged and sold loose — this is the core
        // domain requirement: selling 1.5kg three times from a 50kg batch
        // must land on exactly 45.5kg, not a float-drifted approximation.
        $category = Category::create(['name' => 'Feeds']);
        $user = User::factory()->owner()->create();
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Dairy Meal',
            'base_unit' => 'kg',
            'buying_price_cents' => 5200,
            'selling_price_cents' => 6500,
            'reorder_level' => 10,
        ]);

        $batch = Batch::create([
            'product_id' => $product->id,
            'batch_number' => 'B1',
            'expiry_date' => Carbon::now()->addMonths(6),
            'quantity_received' => 50,
            'quantity_remaining' => 50,
            'buying_price_cents' => 5200,
            'received_at' => Carbon::now(),
            'created_by' => $user->id,
        ]);

        foreach ([1.5, 1.5, 1.5] as $sold) {
            $batch->quantity_remaining = bcsub((string) $batch->quantity_remaining, (string) $sold, 3);
            $batch->save();
        }

        $this->assertSame('45.500', (string) $batch->fresh()->quantity_remaining);
    }

    public function test_stock_on_hand_sums_across_multiple_batches_with_precision(): void
    {
        $category = Category::create(['name' => 'Feeds']);
        $user = User::factory()->owner()->create();
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Layers Mash',
            'base_unit' => 'kg',
            'buying_price_cents' => 5000,
            'selling_price_cents' => 6200,
            'reorder_level' => 100,
        ]);

        Batch::create([
            'product_id' => $product->id, 'batch_number' => 'B1', 'expiry_date' => null,
            'quantity_received' => 48.5, 'quantity_remaining' => 48.5,
            'buying_price_cents' => 5000, 'received_at' => Carbon::now(), 'created_by' => $user->id,
        ]);
        Batch::create([
            'product_id' => $product->id, 'batch_number' => 'B2', 'expiry_date' => null,
            'quantity_received' => 22.25, 'quantity_remaining' => 22.25,
            'buying_price_cents' => 5000, 'received_at' => Carbon::now(), 'created_by' => $user->id,
        ]);

        $this->assertSame('70.750', $product->stockOnHand());
    }

    public function test_is_low_stock_compares_against_reorder_level(): void
    {
        $category = Category::create(['name' => 'Feeds']);
        $user = User::factory()->owner()->create();
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Chick Mash',
            'base_unit' => 'kg',
            'buying_price_cents' => 5500,
            'selling_price_cents' => 6800,
            'reorder_level' => 50,
        ]);

        Batch::create([
            'product_id' => $product->id, 'batch_number' => 'B1', 'expiry_date' => null,
            'quantity_received' => 40, 'quantity_remaining' => 40,
            'buying_price_cents' => 5500, 'received_at' => Carbon::now(), 'created_by' => $user->id,
        ]);

        $this->assertTrue($product->isLowStock());

        Batch::create([
            'product_id' => $product->id, 'batch_number' => 'B2', 'expiry_date' => null,
            'quantity_received' => 20, 'quantity_remaining' => 20,
            'buying_price_cents' => 5500, 'received_at' => Carbon::now(), 'created_by' => $user->id,
        ]);

        $this->assertFalse($product->fresh()->isLowStock());
    }

    public function test_has_bulk_pack_requires_selling_unit_pack_size_and_pack_price(): void
    {
        $category = Category::create(['name' => 'Feeds']);
        $base = [
            'category_id' => $category->id,
            'name' => 'Chick Mash',
            'base_unit' => 'pcs',
            'buying_price_cents' => 4000,
            'selling_price_cents' => 60,
            'reorder_level' => 1,
        ];

        $noConversion = Product::create($base + ['name' => 'No conversion', 'pack_size' => 50, 'pack_price_cents' => 250000]);
        $this->assertFalse($noConversion->hasBulkPack(), 'no selling_unit means no bulk pack, even with pack fields set');

        $noPackFields = Product::create($base + ['name' => 'No pack fields', 'selling_unit' => 'kg', 'units_per_base' => 50]);
        $this->assertFalse($noPackFields->hasBulkPack());

        $full = Product::create($base + ['name' => 'Full config', 'selling_unit' => 'kg', 'units_per_base' => 50, 'pack_size' => 50, 'pack_price_cents' => 250000]);
        $this->assertTrue($full->hasBulkPack());
    }

    public function test_pack_unit_price_cents_divides_pack_price_by_pack_size(): void
    {
        $category = Category::create(['name' => 'Feeds']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Chick Mash',
            'base_unit' => 'pcs',
            'selling_unit' => 'kg',
            'units_per_base' => 50,
            'pack_size' => 50,
            'pack_price_cents' => 250000,
            'buying_price_cents' => 4000,
            'selling_price_cents' => 60,
            'reorder_level' => 1,
        ]);

        // KES 2,500 for a 50kg bag = KES 50/kg bulk rate, cheaper than the
        // KES 60/kg retail rate — the whole point of the feature.
        $this->assertSame(5000, $product->packUnitPriceCents());
    }
}
