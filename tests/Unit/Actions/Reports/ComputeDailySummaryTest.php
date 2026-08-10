<?php

namespace Tests\Unit\Actions\Reports;

use App\Actions\Reports\ComputeDailySummary;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Carbon;

class ComputeDailySummaryTest extends ReportsActionTestCase
{
    public function test_basic_cash_sale_computes_revenue_cogs_and_profit(): void
    {
        $user = User::factory()->attendant()->create();
        $product = $this->makeProduct(['buying_price_cents' => 5000, 'selling_price_cents' => 6500]);
        $this->makeBatch($product, $user);
        $today = Carbon::today();

        $this->completeSale(
            [['product_id' => $product->id, 'quantity' => '2', 'unit_price_cents' => 6500]],
            null,
            [['method' => Payment::METHOD_CASH, 'amount_cents' => 13000]],
            $user,
            $today,
        );

        $summary = (new ComputeDailySummary)($today);

        $this->assertSame(1, $summary['transaction_count']);
        $this->assertSame(13000, $summary['gross_sales_cents']);
        $this->assertSame(10000, $summary['cogs_cents']); // 2 * 5000
        $this->assertSame(3000, $summary['profit_cents']); // 13000 - 10000
    }

    public function test_payment_methods_are_broken_down_correctly(): void
    {
        $user = User::factory()->attendant()->create();
        $product = $this->makeProduct(['selling_price_cents' => 10000]);
        $this->makeBatch($product, $user);
        $customer = $this->makeCustomer();
        $today = Carbon::today();

        $this->completeSale(
            [['product_id' => $product->id, 'quantity' => '1', 'unit_price_cents' => 10000]],
            $customer,
            [
                ['method' => Payment::METHOD_CASH, 'amount_cents' => 4000],
                ['method' => Payment::METHOD_CREDIT, 'amount_cents' => 6000],
            ],
            $user,
            $today,
        );

        $summary = (new ComputeDailySummary)($today);

        $this->assertSame(4000, $summary['by_payment_method']['cash']);
        $this->assertSame(6000, $summary['by_payment_method']['credit']);
        $this->assertSame(0, $summary['by_payment_method']['mpesa']);
    }

    public function test_sale_on_a_different_day_is_excluded(): void
    {
        $user = User::factory()->attendant()->create();
        $product = $this->makeProduct(['selling_price_cents' => 6500]);
        $this->makeBatch($product, $user);

        $this->completeSale(
            [['product_id' => $product->id, 'quantity' => '1', 'unit_price_cents' => 6500]],
            null,
            [['method' => Payment::METHOD_CASH, 'amount_cents' => 6500]],
            $user,
            Carbon::yesterday(),
        );

        $summary = (new ComputeDailySummary)(Carbon::today());

        $this->assertSame(0, $summary['transaction_count']);
        $this->assertSame(0, $summary['gross_sales_cents']);
    }

    public function test_cash_expected_equals_cash_payments_when_no_returns(): void
    {
        $user = User::factory()->attendant()->create();
        $product = $this->makeProduct(['selling_price_cents' => 6500]);
        $this->makeBatch($product, $user);
        $today = Carbon::today();

        $this->completeSale(
            [['product_id' => $product->id, 'quantity' => '2', 'unit_price_cents' => 6500]],
            null,
            [['method' => Payment::METHOD_CASH, 'amount_cents' => 13000]],
            $user,
            $today,
        );

        $summary = (new ComputeDailySummary)($today);

        $this->assertSame(13000, $summary['cash_expected_cents']);
    }

    public function test_return_on_cash_sale_reduces_cash_expected(): void
    {
        $cashier = User::factory()->attendant()->create();
        $manager = User::factory()->manager()->create();
        $product = $this->makeProduct(['selling_price_cents' => 6500]);
        $this->makeBatch($product, $cashier);
        $today = Carbon::today();

        $sale = $this->completeSale(
            [['product_id' => $product->id, 'quantity' => '2', 'unit_price_cents' => 6500]],
            null,
            [['method' => Payment::METHOD_CASH, 'amount_cents' => 13000]],
            $cashier,
            $today,
        );
        $line = $sale->lines->first();

        $this->processReturn($sale, [['sale_line_id' => $line->id, 'quantity_returned' => '1']], 'Wrong item', $manager, $cashier, $today);

        $summary = (new ComputeDailySummary)($today);

        $this->assertSame(6500, $summary['cash_expected_cents']); // 13000 cash - 6500 cash refund
    }

    public function test_return_on_credit_sale_does_not_reduce_cash_expected(): void
    {
        $cashier = User::factory()->attendant()->create();
        $manager = User::factory()->manager()->create();
        $product = $this->makeProduct(['selling_price_cents' => 6500]);
        $this->makeBatch($product, $cashier);
        $customer = $this->makeCustomer();
        $today = Carbon::today();

        $sale = $this->completeSale(
            [['product_id' => $product->id, 'quantity' => '2', 'unit_price_cents' => 6500]],
            $customer,
            [['method' => Payment::METHOD_CREDIT, 'amount_cents' => 13000]],
            $cashier,
            $today,
        );
        $line = $sale->lines->first();

        $this->processReturn($sale, [['sale_line_id' => $line->id, 'quantity_returned' => '1']], 'Wrong item', $manager, $cashier, $today);

        $summary = (new ComputeDailySummary)($today);

        // No cash was ever collected, so no cash should be considered refunded.
        $this->assertSame(0, $summary['cash_expected_cents']);
    }

    public function test_return_reduces_net_revenue_and_profit_proportionally(): void
    {
        $cashier = User::factory()->attendant()->create();
        $manager = User::factory()->manager()->create();
        $product = $this->makeProduct(['buying_price_cents' => 5000, 'selling_price_cents' => 6500]);
        $this->makeBatch($product, $cashier);
        $today = Carbon::today();

        $sale = $this->completeSale(
            [['product_id' => $product->id, 'quantity' => '4', 'unit_price_cents' => 6500]], // revenue 26000, cogs 20000
            null,
            [['method' => Payment::METHOD_CASH, 'amount_cents' => 26000]],
            $cashier,
            $today,
        );
        $line = $sale->lines->first();

        // Return 1 of the 4 units: refund 6500, cogs reversed 5000 (proportional).
        $this->processReturn($sale, [['sale_line_id' => $line->id, 'quantity_returned' => '1']], 'Return', $manager, $cashier, $today);

        $summary = (new ComputeDailySummary)($today);

        $this->assertSame(26000, $summary['gross_sales_cents']);
        $this->assertSame(6500, $summary['returns_cents']);
        $this->assertSame(19500, $summary['net_revenue_cents']); // 26000 - 6500
        $this->assertSame(15000, $summary['cogs_cents']); // 20000 - 5000 reversed
        $this->assertSame(4500, $summary['profit_cents']); // 19500 - 15000
    }

    public function test_discount_cents_sums_discounts_from_completed_sales(): void
    {
        $manager = User::factory()->manager()->create();
        $product = $this->makeProduct(['selling_price_cents' => 6500]);
        $this->makeBatch($product, $manager);
        $today = Carbon::today();

        $this->completeSale(
            [['product_id' => $product->id, 'quantity' => '2', 'unit_price_cents' => 6500, 'discount_cents' => 1000]],
            null,
            [['method' => Payment::METHOD_CASH, 'amount_cents' => 12000]],
            $manager,
            $today,
        );

        $summary = (new ComputeDailySummary)($today);

        $this->assertSame(1000, $summary['discount_cents']);
    }

    public function test_net_profit_subtracts_todays_expenses_from_gross_profit(): void
    {
        $user = User::factory()->attendant()->create();
        $manager = User::factory()->manager()->create();
        $product = $this->makeProduct(['buying_price_cents' => 5000, 'selling_price_cents' => 6500]);
        $this->makeBatch($product, $user);
        $today = Carbon::today();
        $category = ExpenseCategory::create(['name' => 'Rent']);

        $this->completeSale(
            [['product_id' => $product->id, 'quantity' => '2', 'unit_price_cents' => 6500]],
            null,
            [['method' => Payment::METHOD_CASH, 'amount_cents' => 13000]],
            $user,
            $today,
        );
        Expense::create([
            'expense_category_id' => $category->id,
            'amount_cents' => 1000,
            'incurred_on' => $today,
            'created_by' => $manager->id,
        ]);

        $summary = (new ComputeDailySummary)($today);

        $this->assertSame(3000, $summary['profit_cents']); // 13000 - 10000 cogs
        $this->assertSame(1000, $summary['expenses_cents']);
        $this->assertSame(2000, $summary['net_profit_cents']); // 3000 - 1000
    }

    public function test_expenses_on_a_different_day_are_excluded_from_todays_summary(): void
    {
        $manager = User::factory()->manager()->create();
        $category = ExpenseCategory::create(['name' => 'Rent']);
        Expense::create([
            'expense_category_id' => $category->id,
            'amount_cents' => 1000,
            'incurred_on' => Carbon::yesterday(),
            'created_by' => $manager->id,
        ]);

        $summary = (new ComputeDailySummary)(Carbon::today());

        $this->assertSame(0, $summary['expenses_cents']);
    }

    public function test_summary_can_be_scoped_to_a_single_attendant(): void
    {
        $alice = User::factory()->attendant()->create();
        $bob = User::factory()->attendant()->create();
        $product = $this->makeProduct(['selling_price_cents' => 6500]);
        $this->makeBatch($product, $alice, ['quantity_remaining' => 100]);
        $today = Carbon::today();

        $this->completeSale(
            [['product_id' => $product->id, 'quantity' => '1', 'unit_price_cents' => 6500]],
            null, [['method' => Payment::METHOD_CASH, 'amount_cents' => 6500]], $alice, $today,
        );
        $this->completeSale(
            [['product_id' => $product->id, 'quantity' => '2', 'unit_price_cents' => 6500]],
            null, [['method' => Payment::METHOD_CASH, 'amount_cents' => 13000]], $bob, $today,
        );

        $aliceSummary = (new ComputeDailySummary)($today, $alice);
        $bobSummary = (new ComputeDailySummary)($today, $bob);

        $this->assertSame(1, $aliceSummary['transaction_count']);
        $this->assertSame(6500, $aliceSummary['gross_sales_cents']);
        $this->assertSame(1, $bobSummary['transaction_count']);
        $this->assertSame(13000, $bobSummary['gross_sales_cents']);
    }

    public function test_voided_sales_are_excluded_from_the_summary(): void
    {
        $user = User::factory()->attendant()->create();
        $product = $this->makeProduct(['selling_price_cents' => 6500]);
        $this->makeBatch($product, $user);
        $today = Carbon::today();

        $sale = $this->completeSale(
            [['product_id' => $product->id, 'quantity' => '1', 'unit_price_cents' => 6500]],
            null, [['method' => Payment::METHOD_CASH, 'amount_cents' => 6500]], $user, $today,
        );
        $sale->update(['status' => Sale::STATUS_VOIDED]);

        $summary = (new ComputeDailySummary)($today);

        $this->assertSame(0, $summary['transaction_count']);
        $this->assertSame(0, $summary['gross_sales_cents']);
    }
}
