<?php

namespace Tests\Feature\Inventory;

use App\Livewire\Inventory\Products\Show;
use App\Models\Batch;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class BatchManagementTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create(['name' => 'Feeds']);
        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Dairy Meal',
            'base_unit' => 'kg',
            'buying_price_cents' => 5200,
            'selling_price_cents' => 6500,
            'reorder_level' => 10,
        ]);
    }

    public function test_attendant_can_view_product_page_but_not_buying_price(): void
    {
        $attendant = User::factory()->attendant()->create();

        $response = $this->actingAs($attendant)->get(route('inventory.products.show', $this->product));

        $response->assertOk();
        $response->assertDontSee('Buying price');
        $response->assertDontSee('Receive stock');
    }

    public function test_manager_can_adjust_stock_with_a_reason(): void
    {
        $manager = User::factory()->manager()->create();
        $batch = Batch::create([
            'product_id' => $this->product->id, 'batch_number' => 'B1', 'expiry_date' => null,
            'quantity_received' => 50, 'quantity_remaining' => 50,
            'buying_price_cents' => 5200, 'received_at' => Carbon::now(), 'created_by' => $manager->id,
        ]);

        Livewire::actingAs($manager)
            ->test(Show::class, ['product' => $this->product])
            ->call('startAdjust', $batch->id)
            ->set('quantityDelta', '-3')
            ->set('reason', StockAdjustment::REASON_DAMAGE)
            ->set('notes', 'Torn bag')
            ->call('adjustStock');

        $this->assertSame('47.000', (string) $batch->fresh()->quantity_remaining);
        $this->assertDatabaseHas('stock_adjustments', ['batch_id' => $batch->id, 'reason' => StockAdjustment::REASON_DAMAGE]);
    }

    public function test_adjustment_without_a_reason_fails_validation(): void
    {
        $manager = User::factory()->manager()->create();
        $batch = Batch::create([
            'product_id' => $this->product->id, 'batch_number' => 'B1', 'expiry_date' => null,
            'quantity_received' => 50, 'quantity_remaining' => 50,
            'buying_price_cents' => 5200, 'received_at' => Carbon::now(), 'created_by' => $manager->id,
        ]);

        Livewire::actingAs($manager)
            ->test(Show::class, ['product' => $this->product])
            ->call('startAdjust', $batch->id)
            ->set('quantityDelta', '-3')
            ->set('reason', '')
            ->call('adjustStock')
            ->assertHasErrors('reason');

        $this->assertSame('50.000', (string) $batch->fresh()->quantity_remaining);
    }

    public function test_adjustment_that_would_go_negative_shows_error_and_does_not_persist(): void
    {
        $manager = User::factory()->manager()->create();
        $batch = Batch::create([
            'product_id' => $this->product->id, 'batch_number' => 'B1', 'expiry_date' => null,
            'quantity_received' => 50, 'quantity_remaining' => 50,
            'buying_price_cents' => 5200, 'received_at' => Carbon::now(), 'created_by' => $manager->id,
        ]);

        Livewire::actingAs($manager)
            ->test(Show::class, ['product' => $this->product])
            ->call('startAdjust', $batch->id)
            ->set('quantityDelta', '-999')
            ->set('reason', StockAdjustment::REASON_THEFT)
            ->call('adjustStock')
            ->assertHasErrors('quantityDelta');

        $this->assertSame('50.000', (string) $batch->fresh()->quantity_remaining);
        $this->assertDatabaseCount('stock_adjustments', 0);
    }
}
