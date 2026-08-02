<?php

namespace Tests\Unit\Actions\Sales;

use App\Actions\Sales\HoldSale;
use App\Actions\Sales\ResumeSale;
use App\Models\Sale;
use App\Models\User;
use InvalidArgumentException;
use RuntimeException;

class HoldResumeSaleTest extends SalesActionTestCase
{
    public function test_holding_a_sale_does_not_deduct_stock(): void
    {
        $user = User::factory()->attendant()->create();
        $product = $this->makeProduct();
        $batch = $this->makeBatch($product, $user, ['quantity_remaining' => 50]);

        (new HoldSale)(
            [['product_id' => $product->id, 'quantity' => '5', 'unit_price_cents' => 6500]],
            null,
            $user,
        );

        $this->assertSame('50.000', (string) $batch->fresh()->quantity_remaining);
    }

    public function test_holding_a_sale_creates_no_payments(): void
    {
        $user = User::factory()->attendant()->create();
        $product = $this->makeProduct();
        $this->makeBatch($product, $user);

        $sale = (new HoldSale)(
            [['product_id' => $product->id, 'quantity' => '2', 'unit_price_cents' => 6500]],
            null,
            $user,
        );

        $this->assertSame(Sale::STATUS_HELD, $sale->status);
        $this->assertSame(0, $sale->payments()->count());
        $this->assertSame(13000, $sale->total_cents);
    }

    public function test_holding_an_empty_cart_is_rejected(): void
    {
        $user = User::factory()->attendant()->create();

        $this->expectException(InvalidArgumentException::class);

        (new HoldSale)([], null, $user);
    }

    public function test_resuming_returns_cart_shaped_lines_and_deletes_the_held_sale(): void
    {
        $user = User::factory()->attendant()->create();
        $product = $this->makeProduct();
        $this->makeBatch($product, $user);
        $customer = $this->makeCustomer();

        $sale = (new HoldSale)(
            [['product_id' => $product->id, 'quantity' => '3', 'unit_price_cents' => 6500, 'discount_cents' => 500]],
            $customer,
            $user,
        );
        $saleId = $sale->id;

        $resumed = (new ResumeSale)($sale);

        $this->assertSame($customer->id, $resumed['customer_id']);
        $this->assertCount(1, $resumed['lines']);
        $this->assertSame($product->id, $resumed['lines'][0]['product_id']);
        $this->assertSame('3.000', $resumed['lines'][0]['quantity']);
        $this->assertSame(500, $resumed['lines'][0]['discount_cents']);
        $this->assertDatabaseMissing('sales', ['id' => $saleId]);
    }

    public function test_cannot_resume_a_completed_sale(): void
    {
        $user = User::factory()->attendant()->create();
        $product = $this->makeProduct();
        $this->makeBatch($product, $user);

        $sale = (new HoldSale)(
            [['product_id' => $product->id, 'quantity' => '1', 'unit_price_cents' => 6500]],
            null,
            $user,
        );
        $sale->update(['status' => Sale::STATUS_COMPLETED]);

        $this->expectException(RuntimeException::class);

        (new ResumeSale)($sale);
    }
}
