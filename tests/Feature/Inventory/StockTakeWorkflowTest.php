<?php

namespace Tests\Feature\Inventory;

use App\Livewire\Inventory\StockTakes\Index as StockTakesIndex;
use App\Livewire\Inventory\StockTakes\Show as StockTakeShow;
use App\Models\Batch;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockTake;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class StockTakeWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private Batch $batch;

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

    private function makeBatch(User $user): Batch
    {
        return Batch::create([
            'product_id' => $this->product->id, 'batch_number' => 'B1', 'expiry_date' => null,
            'quantity_received' => 50, 'quantity_remaining' => 50,
            'buying_price_cents' => 5200, 'received_at' => Carbon::now(), 'created_by' => $user->id,
        ]);
    }

    public function test_attendant_cannot_access_stock_takes(): void
    {
        $attendant = User::factory()->attendant()->create();

        $this->actingAs($attendant)->get(route('inventory.stock-takes.index'))->assertForbidden();
    }

    public function test_manager_can_start_a_stock_take(): void
    {
        $manager = User::factory()->manager()->create();
        $batch = $this->makeBatch($manager);

        Livewire::actingAs($manager)
            ->test(StockTakesIndex::class)
            ->call('start');

        $this->assertDatabaseCount('stock_takes', 1);
        $this->assertDatabaseHas('stock_take_lines', [
            'batch_id' => $batch->id,
            'system_quantity' => 50,
        ]);
    }

    public function test_entering_counts_and_completing_applies_corrections(): void
    {
        $manager = User::factory()->manager()->create();
        $batch = $this->makeBatch($manager);

        $stockTake = StockTake::create([
            'reference' => 'ST-TEST',
            'status' => StockTake::STATUS_IN_PROGRESS,
            'started_by' => $manager->id,
            'started_at' => Carbon::now(),
        ]);
        $line = $stockTake->lines()->create([
            'batch_id' => $batch->id,
            'system_quantity' => 50,
            'counted_quantity' => null,
        ]);

        Livewire::actingAs($manager)
            ->test(StockTakeShow::class, ['stockTake' => $stockTake])
            ->set("counts.{$line->id}", '46.5')
            ->call('complete');

        $this->assertSame('46.500', (string) $batch->fresh()->quantity_remaining);
        $this->assertSame(StockTake::STATUS_COMPLETED, $stockTake->fresh()->status);
        $this->assertDatabaseHas('stock_adjustments', [
            'batch_id' => $batch->id,
            'reason' => 'stock_take',
        ]);
    }

    public function test_save_counts_persists_progress_without_completing(): void
    {
        $manager = User::factory()->manager()->create();
        $batch = $this->makeBatch($manager);

        $stockTake = StockTake::create([
            'reference' => 'ST-TEST',
            'status' => StockTake::STATUS_IN_PROGRESS,
            'started_by' => $manager->id,
            'started_at' => Carbon::now(),
        ]);
        $line = $stockTake->lines()->create([
            'batch_id' => $batch->id,
            'system_quantity' => 50,
            'counted_quantity' => null,
        ]);

        Livewire::actingAs($manager)
            ->test(StockTakeShow::class, ['stockTake' => $stockTake])
            ->set("counts.{$line->id}", '48')
            ->call('saveCounts');

        $this->assertSame('48.000', (string) $line->fresh()->counted_quantity);
        // Stock is not yet touched — completion is a separate, explicit step.
        $this->assertSame('50.000', (string) $batch->fresh()->quantity_remaining);
        $this->assertSame(StockTake::STATUS_IN_PROGRESS, $stockTake->fresh()->status);
    }

    public function test_completing_a_second_time_is_a_no_op(): void
    {
        $manager = User::factory()->manager()->create();
        $batch = $this->makeBatch($manager);

        $stockTake = StockTake::create([
            'reference' => 'ST-TEST',
            'status' => StockTake::STATUS_IN_PROGRESS,
            'started_by' => $manager->id,
            'started_at' => Carbon::now(),
        ]);
        $line = $stockTake->lines()->create([
            'batch_id' => $batch->id,
            'system_quantity' => 50,
            'counted_quantity' => null,
        ]);

        $component = Livewire::actingAs($manager)
            ->test(StockTakeShow::class, ['stockTake' => $stockTake])
            ->set("counts.{$line->id}", '45')
            ->call('complete');

        $this->assertSame('45.000', (string) $batch->fresh()->quantity_remaining);
        $this->assertDatabaseCount('stock_adjustments', 1);

        // Re-mount against the now-completed stock take and try again.
        Livewire::actingAs($manager)
            ->test(StockTakeShow::class, ['stockTake' => $stockTake->fresh()])
            ->set("counts.{$line->id}", '10')
            ->call('complete');

        $this->assertSame('45.000', (string) $batch->fresh()->quantity_remaining);
        $this->assertDatabaseCount('stock_adjustments', 1);
    }
}
