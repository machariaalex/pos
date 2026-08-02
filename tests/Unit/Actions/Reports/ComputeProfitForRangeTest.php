<?php

namespace Tests\Unit\Actions\Reports;

use App\Actions\Reports\ComputeProfitForRange;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;

class ComputeProfitForRangeTest extends ReportsActionTestCase
{
    public function test_basic_range_computes_revenue_cogs_and_profit(): void
    {
        $user = User::factory()->attendant()->create();
        $product = $this->makeProduct(['buying_price_cents' => 5000, 'selling_price_cents' => 6500]);
        $this->makeBatch($product, $user);

        $this->completeSale(
            [['product_id' => $product->id, 'quantity' => '2', 'unit_price_cents' => 6500]],
            null, [['method' => Payment::METHOD_CASH, 'amount_cents' => 13000]], $user, Carbon::today(),
        );

        $result = (new ComputeProfitForRange)(Carbon::today()->subDays(7), Carbon::today());

        $this->assertSame(13000, $result['revenue_cents']);
        $this->assertSame(10000, $result['cogs_cents']);
        $this->assertSame(3000, $result['profit_cents']);
    }

    public function test_sales_outside_range_are_excluded(): void
    {
        $user = User::factory()->attendant()->create();
        $product = $this->makeProduct(['selling_price_cents' => 6500]);
        $this->makeBatch($product, $user);

        $this->completeSale(
            [['product_id' => $product->id, 'quantity' => '1', 'unit_price_cents' => 6500]],
            null, [['method' => Payment::METHOD_CASH, 'amount_cents' => 6500]], $user, Carbon::today()->subDays(40),
        );

        $result = (new ComputeProfitForRange)(Carbon::today()->subDays(29), Carbon::today());

        $this->assertSame(0, $result['revenue_cents']);
    }

    public function test_return_within_range_reduces_revenue_and_reverses_proportional_cogs(): void
    {
        $cashier = User::factory()->attendant()->create();
        $manager = User::factory()->manager()->create();
        $product = $this->makeProduct(['buying_price_cents' => 5000, 'selling_price_cents' => 6500]);
        $this->makeBatch($product, $cashier);

        $sale = $this->completeSale(
            [['product_id' => $product->id, 'quantity' => '4', 'unit_price_cents' => 6500]],
            null, [['method' => Payment::METHOD_CASH, 'amount_cents' => 26000]], $cashier, Carbon::today(),
        );
        $line = $sale->lines->first();

        $this->processReturn($sale, [['sale_line_id' => $line->id, 'quantity_returned' => '1']], 'Return', $manager, $cashier, Carbon::today());

        $result = (new ComputeProfitForRange)(Carbon::today()->subDays(7), Carbon::today());

        $this->assertSame(19500, $result['revenue_cents']); // 26000 - 6500
        $this->assertSame(15000, $result['cogs_cents']); // 20000 - 5000
        $this->assertSame(4500, $result['profit_cents']);
    }

    public function test_margin_percent_is_computed_correctly(): void
    {
        $user = User::factory()->attendant()->create();
        $product = $this->makeProduct(['buying_price_cents' => 5000, 'selling_price_cents' => 10000]);
        $this->makeBatch($product, $user);

        $this->completeSale(
            [['product_id' => $product->id, 'quantity' => '1', 'unit_price_cents' => 10000]],
            null, [['method' => Payment::METHOD_CASH, 'amount_cents' => 10000]], $user, Carbon::today(),
        );

        $result = (new ComputeProfitForRange)(Carbon::today(), Carbon::today());

        $this->assertSame(50.0, $result['margin_percent']); // (10000-5000)/10000 = 50%
    }

    public function test_zero_revenue_range_does_not_divide_by_zero(): void
    {
        $result = (new ComputeProfitForRange)(Carbon::today()->subDays(7), Carbon::today());

        $this->assertSame(0, $result['revenue_cents']);
        $this->assertSame(0.0, $result['margin_percent']);
    }
}
