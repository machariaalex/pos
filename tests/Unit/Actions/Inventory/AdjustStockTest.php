<?php

namespace Tests\Unit\Actions\Inventory;

use App\Actions\Inventory\AdjustStock;
use App\Models\Batch;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockTake;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

class AdjustStockTest extends TestCase
{
    use RefreshDatabase;

    private Batch $batch;

    private User $user;

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
        $this->batch = Batch::create([
            'product_id' => $product->id,
            'batch_number' => 'B1',
            'expiry_date' => Carbon::now()->addMonths(6),
            'quantity_received' => 50,
            'quantity_remaining' => 50,
            'buying_price_cents' => 5200,
            'received_at' => Carbon::now(),
            'created_by' => $this->user->id,
        ]);
    }

    public function test_negative_delta_reduces_remaining_stock(): void
    {
        (new AdjustStock)($this->batch, '-2.5', StockAdjustment::REASON_DAMAGE, 'Torn bag', $this->user);

        $this->assertSame('47.500', (string) $this->batch->fresh()->quantity_remaining);
    }

    public function test_positive_delta_increases_remaining_stock(): void
    {
        (new AdjustStock)($this->batch, '3.250', StockAdjustment::REASON_COUNT_CORRECTION, 'Found extra bags', $this->user);

        $this->assertSame('53.250', (string) $this->batch->fresh()->quantity_remaining);
    }

    public function test_adjustment_below_zero_is_rejected_and_nothing_persists(): void
    {
        $this->expectException(InvalidArgumentException::class);

        try {
            (new AdjustStock)($this->batch, '-999', StockAdjustment::REASON_THEFT, null, $this->user);
        } finally {
            $this->assertSame('50.000', (string) $this->batch->fresh()->quantity_remaining);
            $this->assertDatabaseCount('stock_adjustments', 0);
        }
    }

    public function test_invalid_reason_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new AdjustStock)($this->batch, '-1', 'made_up_reason', null, $this->user);
    }

    public function test_creates_stock_adjustment_record_with_reason_and_notes(): void
    {
        $adjustment = (new AdjustStock)($this->batch, '-5', StockAdjustment::REASON_EXPIRY_WRITEOFF, 'Past expiry, discarded', $this->user);

        $this->assertDatabaseHas('stock_adjustments', [
            'id' => $adjustment->id,
            'batch_id' => $this->batch->id,
            'reason' => StockAdjustment::REASON_EXPIRY_WRITEOFF,
            'notes' => 'Past expiry, discarded',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_every_adjustment_is_audit_logged(): void
    {
        (new AdjustStock)($this->batch, '-1', StockAdjustment::REASON_THEFT, 'Missing at close', $this->user);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'stock.adjusted',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_stock_take_id_is_recorded_when_provided(): void
    {
        $stockTake = StockTake::create([
            'reference' => 'ST-TEST',
            'status' => StockTake::STATUS_IN_PROGRESS,
            'started_by' => $this->user->id,
            'started_at' => Carbon::now(),
        ]);

        $adjustment = (new AdjustStock)($this->batch, '1', StockAdjustment::REASON_STOCK_TAKE, null, $this->user, stockTakeId: $stockTake->id);

        $this->assertSame($stockTake->id, $adjustment->stock_take_id);
    }
}
