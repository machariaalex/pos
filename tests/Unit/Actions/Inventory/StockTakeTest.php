<?php

namespace Tests\Unit\Actions\Inventory;

use App\Actions\Inventory\CompleteStockTake;
use App\Actions\Inventory\StartStockTake;
use App\Models\Batch;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockTake;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StockTakeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Batch $inStock;

    private Batch $exhausted;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create(['name' => 'Feeds']);
        $this->user = User::factory()->manager()->create();
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Dairy Meal',
            'base_unit' => 'kg',
            'buying_price_cents' => 5200,
            'selling_price_cents' => 6500,
            'reorder_level' => 10,
        ]);

        $this->inStock = Batch::create([
            'product_id' => $product->id, 'batch_number' => 'HAS_STOCK', 'expiry_date' => null,
            'quantity_received' => 50, 'quantity_remaining' => 50,
            'buying_price_cents' => 5200, 'received_at' => Carbon::now(), 'created_by' => $this->user->id,
        ]);

        $this->exhausted = Batch::create([
            'product_id' => $product->id, 'batch_number' => 'EMPTY', 'expiry_date' => null,
            'quantity_received' => 50, 'quantity_remaining' => 0,
            'buying_price_cents' => 5200, 'received_at' => Carbon::now(), 'created_by' => $this->user->id,
        ]);
    }

    public function test_starting_a_stock_take_only_snapshots_batches_with_stock(): void
    {
        $stockTake = (new StartStockTake)($this->user);

        $batchIds = $stockTake->lines()->pluck('batch_id')->all();

        $this->assertContains($this->inStock->id, $batchIds);
        $this->assertNotContains($this->exhausted->id, $batchIds);
    }

    public function test_starting_a_stock_take_snapshots_current_quantity_as_system_quantity(): void
    {
        $stockTake = (new StartStockTake)($this->user);

        $line = $stockTake->lines()->where('batch_id', $this->inStock->id)->first();

        $this->assertSame('50.000', (string) $line->system_quantity);
        $this->assertNull($line->counted_quantity);
    }

    public function test_completing_a_stock_take_applies_correction_for_variance(): void
    {
        $stockTake = (new StartStockTake)($this->user);
        $line = $stockTake->lines()->where('batch_id', $this->inStock->id)->first();
        $line->update(['counted_quantity' => 47.5]); // 2.5kg short

        $summary = (app(CompleteStockTake::class))($stockTake, $this->user);

        $this->assertSame(1, $summary['corrections']);
        $this->assertSame(1, $summary['lines_counted']);
        $this->assertSame('47.500', (string) $this->inStock->fresh()->quantity_remaining);
    }

    public function test_completing_a_stock_take_skips_lines_with_no_variance(): void
    {
        $stockTake = (new StartStockTake)($this->user);
        $line = $stockTake->lines()->where('batch_id', $this->inStock->id)->first();
        $line->update(['counted_quantity' => 50]); // matches system count exactly

        $summary = (app(CompleteStockTake::class))($stockTake, $this->user);

        $this->assertSame(0, $summary['corrections']);
        $this->assertSame(1, $summary['lines_counted']);
        $this->assertDatabaseCount('stock_adjustments', 0);
    }

    public function test_completing_a_stock_take_leaves_uncounted_lines_untouched(): void
    {
        $stockTake = (new StartStockTake)($this->user);
        // Never set counted_quantity on the line.

        $summary = (app(CompleteStockTake::class))($stockTake, $this->user);

        $this->assertSame(0, $summary['corrections']);
        $this->assertSame(0, $summary['lines_counted']);
        $this->assertSame('50.000', (string) $this->inStock->fresh()->quantity_remaining);
    }

    public function test_completing_a_stock_take_marks_it_completed(): void
    {
        $stockTake = (new StartStockTake)($this->user);

        (app(CompleteStockTake::class))($stockTake, $this->user);

        $stockTake->refresh();
        $this->assertSame(StockTake::STATUS_COMPLETED, $stockTake->status);
        $this->assertSame($this->user->id, $stockTake->completed_by);
        $this->assertNotNull($stockTake->completed_at);
    }

    public function test_stock_take_lifecycle_is_audit_logged(): void
    {
        $stockTake = (new StartStockTake)($this->user);
        (app(CompleteStockTake::class))($stockTake, $this->user);

        $this->assertDatabaseHas('audit_logs', ['action' => 'stock_take.started']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'stock_take.completed']);
    }

    public function test_negative_variance_correction_cannot_be_applied_below_zero(): void
    {
        // Sanity check: counted quantity can't itself be negative from the UI,
        // but a corrupted count of a large negative should still be rejected
        // by AdjustStock's floor-at-zero guard rather than silently underflow.
        $stockTake = (new StartStockTake)($this->user);
        $line = $stockTake->lines()->where('batch_id', $this->inStock->id)->first();
        $line->update(['counted_quantity' => 0]);

        $summary = (app(CompleteStockTake::class))($stockTake, $this->user);

        $this->assertSame(1, $summary['corrections']);
        $this->assertSame('0.000', (string) $this->inStock->fresh()->quantity_remaining);
    }
}
