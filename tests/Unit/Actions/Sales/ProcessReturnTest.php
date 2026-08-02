<?php

namespace Tests\Unit\Actions\Sales;

use App\Actions\Sales\ProcessReturn;
use App\Models\Payment;
use App\Models\User;
use InvalidArgumentException;

class ProcessReturnTest extends CompleteSaleTestBase
{
    public function test_full_return_restocks_the_batch(): void
    {
        $cashier = User::factory()->attendant()->create();
        $manager = User::factory()->manager()->create();
        $product = $this->makeProduct(['selling_price_cents' => 6500]);
        $batch = $this->makeBatch($product, $cashier, ['quantity_remaining' => 50]);

        $sale = $this->complete(
            lines: [['product_id' => $product->id, 'quantity' => '5', 'unit_price_cents' => 6500]],
            customer: null,
            payments: [['method' => Payment::METHOD_CASH, 'amount_cents' => 32500]],
            cashier: $cashier,
        );
        $line = $sale->lines->first();

        (new ProcessReturn)($sale, [['sale_line_id' => $line->id, 'quantity_returned' => '5']], 'Wrong item', $manager, $cashier);

        $this->assertSame('50.000', (string) $batch->fresh()->quantity_remaining);
    }

    public function test_partial_return_refunds_and_restocks_proportionally(): void
    {
        $cashier = User::factory()->attendant()->create();
        $manager = User::factory()->manager()->create();
        $product = $this->makeProduct(['selling_price_cents' => 6500]);
        $batch = $this->makeBatch($product, $cashier, ['quantity_remaining' => 50]);

        $sale = $this->complete(
            lines: [['product_id' => $product->id, 'quantity' => '4', 'unit_price_cents' => 6500]], // total 26000
            customer: null,
            payments: [['method' => Payment::METHOD_CASH, 'amount_cents' => 26000]],
            cashier: $cashier,
        );
        $line = $sale->lines->first();

        $return = (new ProcessReturn)($sale, [['sale_line_id' => $line->id, 'quantity_returned' => '1']], 'Damaged on delivery', $manager, $cashier);

        $this->assertSame(6500, $return->total_refund_cents);
        $this->assertSame('47.000', (string) $batch->fresh()->quantity_remaining); // 46 after sale + 1 back
    }

    public function test_returning_more_than_sold_is_rejected(): void
    {
        $cashier = User::factory()->attendant()->create();
        $manager = User::factory()->manager()->create();
        $product = $this->makeProduct(['selling_price_cents' => 6500]);
        $this->makeBatch($product, $cashier);

        $sale = $this->complete(
            lines: [['product_id' => $product->id, 'quantity' => '2', 'unit_price_cents' => 6500]],
            customer: null,
            payments: [['method' => Payment::METHOD_CASH, 'amount_cents' => 13000]],
            cashier: $cashier,
        );
        $line = $sale->lines->first();

        $this->expectException(InvalidArgumentException::class);

        (new ProcessReturn)($sale, [['sale_line_id' => $line->id, 'quantity_returned' => '5']], 'Too many', $manager, $cashier);
    }

    public function test_returning_the_same_line_twice_cannot_exceed_original_quantity(): void
    {
        $cashier = User::factory()->attendant()->create();
        $manager = User::factory()->manager()->create();
        $product = $this->makeProduct(['selling_price_cents' => 6500]);
        $this->makeBatch($product, $cashier);

        $sale = $this->complete(
            lines: [['product_id' => $product->id, 'quantity' => '3', 'unit_price_cents' => 6500]],
            customer: null,
            payments: [['method' => Payment::METHOD_CASH, 'amount_cents' => 19500]],
            cashier: $cashier,
        );
        $line = $sale->lines->first();

        (new ProcessReturn)($sale, [['sale_line_id' => $line->id, 'quantity_returned' => '2']], 'First return', $manager, $cashier);

        $this->expectException(InvalidArgumentException::class);

        // Only 1 left returnable, asking for 2 more should fail.
        (new ProcessReturn)($sale, [['sale_line_id' => $line->id, 'quantity_returned' => '2']], 'Second return', $manager, $cashier);
    }

    public function test_return_on_a_credit_sale_issues_a_credit_note_instead_of_cash(): void
    {
        $cashier = User::factory()->attendant()->create();
        $manager = User::factory()->manager()->create();
        $product = $this->makeProduct(['selling_price_cents' => 10000]);
        $this->makeBatch($product, $cashier);
        $customer = $this->makeCustomer(['balance_cents' => 0, 'credit_limit_cents' => 1000000]);

        $sale = $this->complete(
            lines: [['product_id' => $product->id, 'quantity' => '2', 'unit_price_cents' => 10000]],
            customer: $customer,
            payments: [['method' => Payment::METHOD_CREDIT, 'amount_cents' => 20000]],
            cashier: $cashier,
        );
        $this->assertSame(20000, $customer->fresh()->balance_cents);
        $line = $sale->lines->first();

        (new ProcessReturn)($sale, [['sale_line_id' => $line->id, 'quantity_returned' => '1']], 'Return half', $manager, $cashier);

        $this->assertSame(10000, $customer->fresh()->balance_cents);
    }

    public function test_return_on_a_cash_sale_does_not_touch_any_ledger(): void
    {
        $cashier = User::factory()->attendant()->create();
        $manager = User::factory()->manager()->create();
        $product = $this->makeProduct(['selling_price_cents' => 6500]);
        $this->makeBatch($product, $cashier);
        $customer = $this->makeCustomer(['balance_cents' => 0]);

        // Cash sale, but customer happens to be attached (e.g. for loyalty tracking) — no credit involved.
        $sale = $this->complete(
            lines: [['product_id' => $product->id, 'quantity' => '2', 'unit_price_cents' => 6500]],
            customer: null,
            payments: [['method' => Payment::METHOD_CASH, 'amount_cents' => 13000]],
            cashier: $cashier,
        );
        $line = $sale->lines->first();

        (new ProcessReturn)($sale, [['sale_line_id' => $line->id, 'quantity_returned' => '1']], 'Not needed', $manager, $cashier);

        $this->assertSame(0, $customer->fresh()->balance_cents);
        $this->assertDatabaseCount('customer_ledger_entries', 0);
    }

    public function test_return_is_audit_logged(): void
    {
        $cashier = User::factory()->attendant()->create();
        $manager = User::factory()->manager()->create();
        $product = $this->makeProduct(['selling_price_cents' => 6500]);
        $this->makeBatch($product, $cashier);

        $sale = $this->complete(
            lines: [['product_id' => $product->id, 'quantity' => '1', 'unit_price_cents' => 6500]],
            customer: null,
            payments: [['method' => Payment::METHOD_CASH, 'amount_cents' => 6500]],
            cashier: $cashier,
        );
        $line = $sale->lines->first();

        (new ProcessReturn)($sale, [['sale_line_id' => $line->id, 'quantity_returned' => '1']], 'Test reason', $manager, $cashier);

        $this->assertDatabaseHas('audit_logs', ['action' => 'sale.refunded', 'user_id' => $cashier->id]);
    }
}
