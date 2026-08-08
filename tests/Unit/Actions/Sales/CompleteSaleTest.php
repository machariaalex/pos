<?php

namespace Tests\Unit\Actions\Sales;

use App\Actions\Sales\AllocateFefoBatches;
use App\Actions\Sales\CompleteSale;
use App\Exceptions\CreditLimitExceededException;
use App\Exceptions\InsufficientStockException;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class CompleteSaleTest extends CompleteSaleTestBase
{
    public function test_cash_sale_computes_totals_correctly(): void
    {
        $user = User::factory()->attendant()->create();
        $product = $this->makeProduct(['selling_price_cents' => 6500]);
        $this->makeBatch($product, $user);

        $sale = $this->complete(
            lines: [['product_id' => $product->id, 'quantity' => '2', 'unit_price_cents' => 6500]],
            customer: null,
            payments: [['method' => Payment::METHOD_CASH, 'amount_cents' => 13000]],
            cashier: $user,
        );

        $this->assertSame(13000, $sale->subtotal_cents);
        $this->assertSame(0, $sale->discount_cents);
        $this->assertSame(13000, $sale->total_cents);
        $this->assertSame(Sale::STATUS_COMPLETED, $sale->status);
    }

    public function test_fractional_quantity_sale_computes_correct_money(): void
    {
        // 1.5kg at KES 65.00/kg = KES 97.50 exactly — this is the case where
        // float multiplication tends to drift; bcmath must not.
        $user = User::factory()->attendant()->create();
        $product = $this->makeProduct(['selling_price_cents' => 6500]);
        $this->makeBatch($product, $user);

        $sale = $this->complete(
            lines: [['product_id' => $product->id, 'quantity' => '1.5', 'unit_price_cents' => 6500]],
            customer: null,
            payments: [['method' => Payment::METHOD_CASH, 'amount_cents' => 9750]],
            cashier: $user,
        );

        $this->assertSame(9750, $sale->total_cents);
    }

    public function test_sale_deducts_stock_via_fefo(): void
    {
        $user = User::factory()->attendant()->create();
        $product = $this->makeProduct();
        $soon = $this->makeBatch($product, $user, ['expiry_date' => Carbon::now()->addDays(10), 'quantity_remaining' => 50]);
        $later = $this->makeBatch($product, $user, ['expiry_date' => Carbon::now()->addDays(90), 'quantity_remaining' => 50]);

        $this->complete(
            lines: [['product_id' => $product->id, 'quantity' => '5', 'unit_price_cents' => 6500]],
            customer: null,
            payments: [['method' => Payment::METHOD_CASH, 'amount_cents' => 32500]],
            cashier: $user,
        );

        $this->assertSame('45.000', (string) $soon->fresh()->quantity_remaining);
        $this->assertSame('50.000', (string) $later->fresh()->quantity_remaining);
    }

    public function test_insufficient_stock_rejects_the_whole_sale(): void
    {
        $user = User::factory()->attendant()->create();
        $product = $this->makeProduct();
        $this->makeBatch($product, $user, ['quantity_remaining' => 2]);

        $this->expectException(InsufficientStockException::class);

        try {
            $this->complete(
                lines: [['product_id' => $product->id, 'quantity' => '10', 'unit_price_cents' => 6500]],
                customer: null,
                payments: [['method' => Payment::METHOD_CASH, 'amount_cents' => 65000]],
                cashier: $user,
            );
        } finally {
            $this->assertDatabaseCount('sales', 0);
        }
    }

    public function test_split_cash_and_mpesa_payment_completes_correctly(): void
    {
        $user = User::factory()->attendant()->create();
        $product = $this->makeProduct(['selling_price_cents' => 10000]);
        $this->makeBatch($product, $user);

        $sale = $this->complete(
            lines: [['product_id' => $product->id, 'quantity' => '1', 'unit_price_cents' => 10000]],
            customer: null,
            payments: [
                ['method' => Payment::METHOD_CASH, 'amount_cents' => 6000],
                ['method' => Payment::METHOD_MPESA, 'amount_cents' => 4000, 'mpesa_code' => 'QAX123XYZ'],
            ],
            cashier: $user,
        );

        $this->assertSame(10000, $sale->total_cents);
        $this->assertSame(2, $sale->payments()->count());
        $this->assertDatabaseHas('payments', ['sale_id' => $sale->id, 'method' => 'mpesa', 'mpesa_code' => 'QAX123XYZ']);
    }

    public function test_mismatched_payment_total_is_rejected(): void
    {
        $user = User::factory()->attendant()->create();
        $product = $this->makeProduct(['selling_price_cents' => 10000]);
        $this->makeBatch($product, $user);

        $this->expectException(InvalidArgumentException::class);

        try {
            $this->complete(
                lines: [['product_id' => $product->id, 'quantity' => '1', 'unit_price_cents' => 10000]],
                customer: null,
                payments: [['method' => Payment::METHOD_CASH, 'amount_cents' => 5000]], // short by 5000
                cashier: $user,
            );
        } finally {
            $this->assertDatabaseCount('sales', 0);
        }
    }

    public function test_credit_sale_charges_the_customer_ledger(): void
    {
        $user = User::factory()->attendant()->create();
        $product = $this->makeProduct(['selling_price_cents' => 10000]);
        $this->makeBatch($product, $user);
        $customer = $this->makeCustomer(['balance_cents' => 0, 'credit_limit_cents' => 1000000]);

        $sale = $this->complete(
            lines: [['product_id' => $product->id, 'quantity' => '1', 'unit_price_cents' => 10000]],
            customer: $customer,
            payments: [['method' => Payment::METHOD_CREDIT, 'amount_cents' => 10000]],
            cashier: $user,
        );

        $this->assertSame(10000, $customer->fresh()->balance_cents);
        $this->assertDatabaseHas('customer_ledger_entries', [
            'customer_id' => $customer->id,
            'type' => 'charge',
            'amount_cents' => 10000,
            'sale_id' => $sale->id,
        ]);
    }

    public function test_split_cash_and_credit_payment_only_charges_the_credit_portion(): void
    {
        $user = User::factory()->attendant()->create();
        $product = $this->makeProduct(['selling_price_cents' => 10000]);
        $this->makeBatch($product, $user);
        $customer = $this->makeCustomer(['balance_cents' => 0, 'credit_limit_cents' => 1000000]);

        $this->complete(
            lines: [['product_id' => $product->id, 'quantity' => '1', 'unit_price_cents' => 10000]],
            customer: $customer,
            payments: [
                ['method' => Payment::METHOD_CASH, 'amount_cents' => 4000],
                ['method' => Payment::METHOD_CREDIT, 'amount_cents' => 6000],
            ],
            cashier: $user,
        );

        $this->assertSame(6000, $customer->fresh()->balance_cents);
    }

    public function test_credit_payment_without_a_customer_is_rejected(): void
    {
        $user = User::factory()->attendant()->create();
        $product = $this->makeProduct(['selling_price_cents' => 10000]);
        $this->makeBatch($product, $user);

        $this->expectException(InvalidArgumentException::class);

        $this->complete(
            lines: [['product_id' => $product->id, 'quantity' => '1', 'unit_price_cents' => 10000]],
            customer: null,
            payments: [['method' => Payment::METHOD_CREDIT, 'amount_cents' => 10000]],
            cashier: $user,
        );
    }

    public function test_credit_sale_exceeding_limit_is_rejected_for_non_owner(): void
    {
        $user = User::factory()->manager()->create();
        $product = $this->makeProduct(['selling_price_cents' => 10000]);
        $this->makeBatch($product, $user);
        $customer = $this->makeCustomer(['balance_cents' => 950000, 'credit_limit_cents' => 1000000]);

        $this->expectException(CreditLimitExceededException::class);

        $this->complete(
            lines: [['product_id' => $product->id, 'quantity' => '10', 'unit_price_cents' => 10000]], // 100 KES over
            customer: $customer,
            payments: [['method' => Payment::METHOD_CREDIT, 'amount_cents' => 100000]],
            cashier: $user,
        );
    }

    public function test_owner_can_override_credit_limit(): void
    {
        $owner = User::factory()->owner()->create();
        $product = $this->makeProduct(['selling_price_cents' => 10000]);
        $this->makeBatch($product, $owner);
        $customer = $this->makeCustomer(['balance_cents' => 950000, 'credit_limit_cents' => 1000000]);

        $sale = (new CompleteSale(app(AllocateFefoBatches::class)))(
            lines: [['product_id' => $product->id, 'quantity' => '10', 'unit_price_cents' => 10000]],
            customer: $customer,
            payments: [['method' => Payment::METHOD_CREDIT, 'amount_cents' => 100000]],
            cashier: $owner,
            overrideCreditLimit: true,
        );

        $this->assertSame(Sale::STATUS_COMPLETED, $sale->status);
        $this->assertSame(1050000, $customer->fresh()->balance_cents);
    }

    public function test_manager_cannot_self_override_credit_limit(): void
    {
        $manager = User::factory()->manager()->create();
        $product = $this->makeProduct(['selling_price_cents' => 10000]);
        $this->makeBatch($product, $manager);
        $customer = $this->makeCustomer(['balance_cents' => 950000, 'credit_limit_cents' => 1000000]);

        $this->expectException(CreditLimitExceededException::class);

        (new CompleteSale(app(AllocateFefoBatches::class)))(
            lines: [['product_id' => $product->id, 'quantity' => '10', 'unit_price_cents' => 10000]],
            customer: $customer,
            payments: [['method' => Payment::METHOD_CREDIT, 'amount_cents' => 100000]],
            cashier: $manager,
            overrideCreditLimit: true, // flag alone isn't enough — manager isn't owner
        );
    }

    public function test_discount_by_attendant_is_rejected(): void
    {
        $attendant = User::factory()->attendant()->create();
        $product = $this->makeProduct(['selling_price_cents' => 10000]);
        $this->makeBatch($product, $attendant);

        $this->expectException(InvalidArgumentException::class);

        $this->complete(
            lines: [['product_id' => $product->id, 'quantity' => '1', 'unit_price_cents' => 10000, 'discount_cents' => 1000]],
            customer: null,
            payments: [['method' => Payment::METHOD_CASH, 'amount_cents' => 9000]],
            cashier: $attendant,
        );
    }

    public function test_discount_by_attendant_with_granted_permission_is_applied_to_total(): void
    {
        // CompleteSale must honor the owner-configurable apply-discount
        // permission, not just the role-based canApprove() check — the
        // Sell screen's Gate::allows('apply-discount') already lets a
        // permitted attendant enter a discount, so this action-layer check
        // must agree or checkout rejects a discount the UI just allowed.
        $attendant = User::factory()->attendant()->create(['permissions' => ['apply-discount']]);
        $product = $this->makeProduct(['selling_price_cents' => 10000]);
        $this->makeBatch($product, $attendant);

        $sale = $this->complete(
            lines: [['product_id' => $product->id, 'quantity' => '1', 'unit_price_cents' => 10000, 'discount_cents' => 1000]],
            customer: null,
            payments: [['method' => Payment::METHOD_CASH, 'amount_cents' => 9000]],
            cashier: $attendant,
        );

        $this->assertSame(1000, $sale->discount_cents);
        $this->assertSame(9000, $sale->total_cents);
    }

    public function test_discount_by_manager_is_applied_to_total(): void
    {
        $manager = User::factory()->manager()->create();
        $product = $this->makeProduct(['selling_price_cents' => 10000]);
        $this->makeBatch($product, $manager);

        $sale = $this->complete(
            lines: [['product_id' => $product->id, 'quantity' => '1', 'unit_price_cents' => 10000, 'discount_cents' => 1000]],
            customer: null,
            payments: [['method' => Payment::METHOD_CASH, 'amount_cents' => 9000]],
            cashier: $manager,
        );

        $this->assertSame(10000, $sale->subtotal_cents);
        $this->assertSame(1000, $sale->discount_cents);
        $this->assertSame(9000, $sale->total_cents);
    }

    public function test_discount_is_audit_logged_as_its_own_action(): void
    {
        $manager = User::factory()->manager()->create();
        $product = $this->makeProduct(['selling_price_cents' => 10000]);
        $this->makeBatch($product, $manager);

        $this->complete(
            lines: [['product_id' => $product->id, 'quantity' => '1', 'unit_price_cents' => 10000, 'discount_cents' => 1000]],
            customer: null,
            payments: [['method' => Payment::METHOD_CASH, 'amount_cents' => 9000]],
            cashier: $manager,
        );

        $this->assertDatabaseHas('audit_logs', ['action' => 'sale.discount_applied', 'user_id' => $manager->id]);
    }

    public function test_no_discount_audit_entry_when_no_discount_applied(): void
    {
        $manager = User::factory()->manager()->create();
        $product = $this->makeProduct(['selling_price_cents' => 10000]);
        $this->makeBatch($product, $manager);

        $this->complete(
            lines: [['product_id' => $product->id, 'quantity' => '1', 'unit_price_cents' => 10000]],
            customer: null,
            payments: [['method' => Payment::METHOD_CASH, 'amount_cents' => 10000]],
            cashier: $manager,
        );

        $this->assertDatabaseMissing('audit_logs', ['action' => 'sale.discount_applied']);
    }

    public function test_sale_line_batch_snapshots_the_actual_cost_for_profit_reporting(): void
    {
        $user = User::factory()->attendant()->create();
        $product = $this->makeProduct(['buying_price_cents' => 5200, 'selling_price_cents' => 6500]);
        $this->makeBatch($product, $user, ['buying_price_cents' => 4800]); // this batch cost less than product default

        $sale = $this->complete(
            lines: [['product_id' => $product->id, 'quantity' => '2', 'unit_price_cents' => 6500]],
            customer: null,
            payments: [['method' => Payment::METHOD_CASH, 'amount_cents' => 13000]],
            cashier: $user,
        );

        $line = $sale->lines->first();
        $this->assertSame(4800 * 2, $line->costCents());
    }

    public function test_empty_cart_is_rejected(): void
    {
        $user = User::factory()->attendant()->create();

        $this->expectException(InvalidArgumentException::class);

        $this->complete(lines: [], customer: null, payments: [], cashier: $user);
    }

    public function test_sale_is_audit_logged(): void
    {
        $user = User::factory()->attendant()->create();
        $product = $this->makeProduct(['selling_price_cents' => 10000]);
        $this->makeBatch($product, $user);

        $this->complete(
            lines: [['product_id' => $product->id, 'quantity' => '1', 'unit_price_cents' => 10000]],
            customer: null,
            payments: [['method' => Payment::METHOD_CASH, 'amount_cents' => 10000]],
            cashier: $user,
        );

        $this->assertDatabaseHas('audit_logs', ['action' => 'sale.completed', 'user_id' => $user->id]);
    }
}
