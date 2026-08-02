<?php

namespace Tests\Unit\Actions\Sales;

use App\Actions\Sales\VoidSale;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use RuntimeException;

class VoidSaleTest extends CompleteSaleTestBase
{
    public function test_voiding_restocks_the_batches_it_drew_from(): void
    {
        $cashier = User::factory()->attendant()->create();
        $manager = User::factory()->manager()->create();
        $product = $this->makeProduct();
        $batch = $this->makeBatch($product, $cashier, ['quantity_remaining' => 50]);

        $sale = $this->complete(
            lines: [['product_id' => $product->id, 'quantity' => '5', 'unit_price_cents' => 6500]],
            customer: null,
            payments: [['method' => Payment::METHOD_CASH, 'amount_cents' => 32500]],
            cashier: $cashier,
        );
        $this->assertSame('45.000', (string) $batch->fresh()->quantity_remaining);

        (new VoidSale)($sale, 'Rang up wrong item', $manager, $cashier);

        $this->assertSame('50.000', (string) $batch->fresh()->quantity_remaining);
    }

    public function test_voiding_a_credit_sale_reverses_the_ledger_charge(): void
    {
        $cashier = User::factory()->attendant()->create();
        $manager = User::factory()->manager()->create();
        $product = $this->makeProduct(['selling_price_cents' => 10000]);
        $this->makeBatch($product, $cashier);
        $customer = $this->makeCustomer(['balance_cents' => 0, 'credit_limit_cents' => 1000000]);

        $sale = $this->complete(
            lines: [['product_id' => $product->id, 'quantity' => '1', 'unit_price_cents' => 10000]],
            customer: $customer,
            payments: [['method' => Payment::METHOD_CREDIT, 'amount_cents' => 10000]],
            cashier: $cashier,
        );
        $this->assertSame(10000, $customer->fresh()->balance_cents);

        (new VoidSale)($sale, 'Customer changed mind', $manager, $cashier);

        $this->assertSame(0, $customer->fresh()->balance_cents);
    }

    public function test_void_marks_status_reason_and_approver(): void
    {
        $cashier = User::factory()->attendant()->create();
        $owner = User::factory()->owner()->create();
        $product = $this->makeProduct();
        $this->makeBatch($product, $cashier);

        $sale = $this->complete(
            lines: [['product_id' => $product->id, 'quantity' => '1', 'unit_price_cents' => 6500]],
            customer: null,
            payments: [['method' => Payment::METHOD_CASH, 'amount_cents' => 6500]],
            cashier: $cashier,
        );

        $voided = (new VoidSale)($sale, 'Duplicate sale', $owner, $cashier);

        $this->assertSame(Sale::STATUS_VOIDED, $voided->status);
        $this->assertSame('Duplicate sale', $voided->void_reason);
        $this->assertSame($owner->id, $voided->approved_by);
        $this->assertNotNull($voided->voided_at);
    }

    public function test_cannot_void_an_already_voided_sale(): void
    {
        $cashier = User::factory()->attendant()->create();
        $owner = User::factory()->owner()->create();
        $product = $this->makeProduct();
        $this->makeBatch($product, $cashier);

        $sale = $this->complete(
            lines: [['product_id' => $product->id, 'quantity' => '1', 'unit_price_cents' => 6500]],
            customer: null,
            payments: [['method' => Payment::METHOD_CASH, 'amount_cents' => 6500]],
            cashier: $cashier,
        );

        (new VoidSale)($sale, 'First void', $owner, $cashier);

        $this->expectException(RuntimeException::class);

        (new VoidSale)($sale->fresh(), 'Second void attempt', $owner, $cashier);
    }

    public function test_void_is_audit_logged(): void
    {
        $cashier = User::factory()->attendant()->create();
        $owner = User::factory()->owner()->create();
        $product = $this->makeProduct();
        $this->makeBatch($product, $cashier);

        $sale = $this->complete(
            lines: [['product_id' => $product->id, 'quantity' => '1', 'unit_price_cents' => 6500]],
            customer: null,
            payments: [['method' => Payment::METHOD_CASH, 'amount_cents' => 6500]],
            cashier: $cashier,
        );

        (new VoidSale)($sale, 'Test reason', $owner, $cashier);

        $this->assertDatabaseHas('audit_logs', ['action' => 'sale.voided', 'user_id' => $cashier->id]);
    }
}
