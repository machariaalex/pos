<?php

namespace Tests\Unit;

use App\Models\Batch;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BatchFefoTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create(['name' => 'Vet Medicines']);
        $this->user = User::factory()->owner()->create();
        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Test Dewormer',
            'base_unit' => 'ml',
            'buying_price_cents' => 500,
            'selling_price_cents' => 800,
            'reorder_level' => 100,
        ]);
    }

    private function makeBatch(string $batchNumber, ?string $expiry, float $remaining = 100): Batch
    {
        return Batch::create([
            'product_id' => $this->product->id,
            'batch_number' => $batchNumber,
            'expiry_date' => $expiry,
            'quantity_received' => $remaining,
            'quantity_remaining' => $remaining,
            'buying_price_cents' => 500,
            'received_at' => Carbon::now(),
            'created_by' => $this->user->id,
        ]);
    }

    public function test_fefo_orders_soonest_expiry_first_regardless_of_insertion_order(): void
    {
        $this->makeBatch('LATE', Carbon::now()->addMonths(12));
        $this->makeBatch('SOON', Carbon::now()->addDays(10));
        $this->makeBatch('MID', Carbon::now()->addMonths(3));

        $ordered = $this->product->batches()->fefoAvailable()->pluck('batch_number')->all();

        $this->assertSame(['SOON', 'MID', 'LATE'], $ordered);
    }

    public function test_fefo_sorts_batches_without_expiry_date_last(): void
    {
        $this->makeBatch('NO_EXPIRY', null);
        $this->makeBatch('EXPIRES', Carbon::now()->addDays(30));

        $ordered = $this->product->batches()->fefoAvailable()->pluck('batch_number')->all();

        $this->assertSame(['EXPIRES', 'NO_EXPIRY'], $ordered);
    }

    public function test_fefo_excludes_batches_with_no_stock_remaining(): void
    {
        $this->makeBatch('EMPTY', Carbon::now()->addDays(5), remaining: 0);
        $this->makeBatch('HAS_STOCK', Carbon::now()->addDays(20), remaining: 10);

        $ordered = $this->product->batches()->fefoAvailable()->pluck('batch_number')->all();

        $this->assertSame(['HAS_STOCK'], $ordered);
    }

    public function test_batch_expired_and_expiring_soon_helpers(): void
    {
        $expired = $this->makeBatch('EXPIRED', Carbon::now()->subDays(5));
        $expiringSoon = $this->makeBatch('SOON', Carbon::now()->addDays(30));
        $notSoon = $this->makeBatch('LATER', Carbon::now()->addDays(90));

        $this->assertTrue($expired->isExpired());
        $this->assertFalse($expired->isExpiringSoon());

        $this->assertFalse($expiringSoon->isExpired());
        $this->assertTrue($expiringSoon->isExpiringSoon(60));

        $this->assertFalse($notSoon->isExpiringSoon(60));
    }
}
