<?php

namespace Tests\Unit\Actions\Sales;

use App\Actions\Sales\AllocateFefoBatches;
use App\Exceptions\InsufficientStockException;
use App\Models\User;
use Illuminate\Support\Carbon;

class AllocateFefoBatchesTest extends SalesActionTestCase
{
    public function test_allocates_entirely_from_soonest_expiring_batch_when_sufficient(): void
    {
        $user = User::factory()->owner()->create();
        $product = $this->makeProduct();
        $soon = $this->makeBatch($product, $user, ['batch_number' => 'SOON', 'expiry_date' => Carbon::now()->addDays(10), 'quantity_remaining' => 20]);
        $this->makeBatch($product, $user, ['batch_number' => 'LATER', 'expiry_date' => Carbon::now()->addDays(90), 'quantity_remaining' => 20]);

        $allocations = (new AllocateFefoBatches)($product, '5');

        $this->assertCount(1, $allocations);
        $this->assertSame($soon->id, $allocations[0]['batch']->id);
        $this->assertSame('5.000', $allocations[0]['quantity']);
    }

    public function test_spills_into_next_batch_when_first_is_insufficient(): void
    {
        $user = User::factory()->owner()->create();
        $product = $this->makeProduct();
        $soon = $this->makeBatch($product, $user, ['batch_number' => 'SOON', 'expiry_date' => Carbon::now()->addDays(10), 'quantity_remaining' => 3]);
        $later = $this->makeBatch($product, $user, ['batch_number' => 'LATER', 'expiry_date' => Carbon::now()->addDays(90), 'quantity_remaining' => 20]);

        $allocations = (new AllocateFefoBatches)($product, '5');

        $this->assertCount(2, $allocations);
        $this->assertSame($soon->id, $allocations[0]['batch']->id);
        $this->assertSame('3.000', $allocations[0]['quantity']);
        $this->assertSame($later->id, $allocations[1]['batch']->id);
        $this->assertSame('2.000', $allocations[1]['quantity']);
    }

    public function test_throws_when_total_stock_is_insufficient(): void
    {
        $user = User::factory()->owner()->create();
        $product = $this->makeProduct();
        $this->makeBatch($product, $user, ['quantity_remaining' => 3]);

        $this->expectException(InsufficientStockException::class);

        (new AllocateFefoBatches)($product, '10');
    }

    public function test_skips_batches_with_zero_remaining(): void
    {
        $user = User::factory()->owner()->create();
        $product = $this->makeProduct();
        $this->makeBatch($product, $user, ['batch_number' => 'EMPTY', 'expiry_date' => Carbon::now()->addDays(5), 'quantity_remaining' => 0]);
        $hasStock = $this->makeBatch($product, $user, ['batch_number' => 'HAS_STOCK', 'expiry_date' => Carbon::now()->addDays(20), 'quantity_remaining' => 10]);

        $allocations = (new AllocateFefoBatches)($product, '4');

        $this->assertCount(1, $allocations);
        $this->assertSame($hasStock->id, $allocations[0]['batch']->id);
    }

    public function test_allocation_preserves_fractional_precision(): void
    {
        $user = User::factory()->owner()->create();
        $product = $this->makeProduct();
        $this->makeBatch($product, $user, ['quantity_remaining' => 2.75]);

        $allocations = (new AllocateFefoBatches)($product, '1.5');

        $this->assertSame('1.500', $allocations[0]['quantity']);
    }
}
