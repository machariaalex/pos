<?php

namespace Tests\Feature\Sales;

use App\Livewire\Sales\Pos;
use App\Models\Batch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class PosTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private Batch $batch;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create(['name' => 'Feeds']);
        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Dairy Meal',
            'base_unit' => 'kg',
            'buying_price_cents' => 5200,
            'selling_price_cents' => 6500,
            'reorder_level' => 10,
        ]);
    }

    private function stockUp(User $user): Batch
    {
        return Batch::create([
            'product_id' => $this->product->id, 'batch_number' => 'B1', 'expiry_date' => Carbon::now()->addMonths(6),
            'quantity_received' => 50, 'quantity_remaining' => 50,
            'buying_price_cents' => 5200, 'received_at' => Carbon::now(), 'created_by' => $user->id,
        ]);
    }

    public function test_attendant_can_access_pos(): void
    {
        $attendant = User::factory()->attendant()->create();

        $this->actingAs($attendant)->get(route('sales.pos'))->assertOk();
    }

    public function test_adding_a_product_twice_increments_quantity_instead_of_duplicating(): void
    {
        $attendant = User::factory()->attendant()->create();
        $this->stockUp($attendant);

        $component = Livewire::actingAs($attendant)
            ->test(Pos::class)
            ->call('addProduct', $this->product->id)
            ->call('addProduct', $this->product->id);

        $cart = $component->get('cart');
        $this->assertCount(1, $cart);
        $this->assertSame('2.000', reset($cart)['quantity']);
    }

    public function test_completing_a_cash_sale_creates_a_completed_sale_and_deducts_stock(): void
    {
        $attendant = User::factory()->attendant()->create();
        $batch = $this->stockUp($attendant);

        Livewire::actingAs($attendant)
            ->test(Pos::class)
            ->call('addProduct', $this->product->id)
            ->call('addPayment', 'cash')
            ->set('payments.0.amount', '65.00')
            ->call('completeSale');

        $sale = Sale::latest()->first();
        $this->assertNotNull($sale);
        $this->assertSame(Sale::STATUS_COMPLETED, $sale->status);
        $this->assertSame(6500, $sale->total_cents);
        $this->assertSame('49.000', (string) $batch->fresh()->quantity_remaining);
    }

    public function test_completing_sale_with_underpayment_is_rejected_server_side(): void
    {
        // The complete button is disabled client-side until remaining == 0,
        // but the server must independently refuse an underpaid sale too.
        $attendant = User::factory()->attendant()->create();
        $this->stockUp($attendant);

        Livewire::actingAs($attendant)
            ->test(Pos::class)
            ->call('addProduct', $this->product->id)
            ->call('addPayment', 'cash')
            ->set('payments.0.amount', '10.00') // short of the 65.00 total
            ->call('completeSale');

        $this->assertDatabaseCount('sales', 0);
    }

    public function test_holding_a_sale_clears_the_cart_and_creates_a_held_sale(): void
    {
        $attendant = User::factory()->attendant()->create();
        $this->stockUp($attendant);

        $component = Livewire::actingAs($attendant)
            ->test(Pos::class)
            ->call('addProduct', $this->product->id)
            ->call('holdSale');

        $this->assertCount(0, $component->get('cart'));
        $this->assertDatabaseHas('sales', ['status' => Sale::STATUS_HELD]);
    }

    public function test_resuming_a_held_sale_repopulates_the_cart(): void
    {
        $attendant = User::factory()->attendant()->create();
        $this->stockUp($attendant);

        $held = Livewire::actingAs($attendant)
            ->test(Pos::class)
            ->call('addProduct', $this->product->id)
            ->call('holdSale');

        $sale = Sale::where('status', Sale::STATUS_HELD)->firstOrFail();

        $component = Livewire::actingAs($attendant)
            ->test(Pos::class)
            ->call('resumeSale', $sale->id);

        $cart = $component->get('cart');
        $this->assertCount(1, $cart);
        $this->assertSame($this->product->id, reset($cart)['product_id']);
        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
    }

    public function test_credit_payment_without_a_customer_shows_an_error(): void
    {
        $attendant = User::factory()->attendant()->create();
        $this->stockUp($attendant);

        Livewire::actingAs($attendant)
            ->test(Pos::class)
            ->call('addProduct', $this->product->id)
            ->call('addPayment', 'credit')
            ->set('payments.0.amount', '65.00')
            ->call('completeSale')
            ->assertHasErrors('payments');

        $this->assertDatabaseCount('sales', 0);
    }

    public function test_credit_sale_with_customer_charges_the_ledger(): void
    {
        $attendant = User::factory()->attendant()->create();
        $this->stockUp($attendant);
        $customer = Customer::create(['name' => 'John Farmer', 'phone' => '0700000000', 'credit_limit_cents' => 1000000, 'balance_cents' => 0]);

        Livewire::actingAs($attendant)
            ->test(Pos::class)
            ->call('addProduct', $this->product->id)
            ->call('selectCustomer', $customer->id)
            ->call('addPayment', 'credit')
            ->set('payments.0.amount', '65.00')
            ->call('completeSale');

        $this->assertSame(6500, $customer->fresh()->balance_cents);
    }

    public function test_attendant_discount_input_has_no_effect_even_if_forced(): void
    {
        $attendant = User::factory()->attendant()->create();
        $this->stockUp($attendant);

        $component = Livewire::actingAs($attendant)
            ->test(Pos::class)
            ->call('addProduct', $this->product->id)
            ->call('updateLineDiscount', 1, '10.00');

        $cart = $component->get('cart');
        $this->assertSame(0, reset($cart)['discount_cents']);
    }

    public function test_manager_discount_reduces_line_total(): void
    {
        $manager = User::factory()->manager()->create();
        $this->stockUp($manager);

        $component = Livewire::actingAs($manager)
            ->test(Pos::class)
            ->call('addProduct', $this->product->id)
            ->call('updateLineDiscount', 1, '10.00');

        $cart = $component->get('cart');
        $this->assertSame(1000, reset($cart)['discount_cents']);
    }

    public function test_credit_sale_over_limit_shows_override_modal_instead_of_completing(): void
    {
        $attendant = User::factory()->attendant()->create();
        $this->stockUp($attendant);
        $customer = Customer::create(['name' => 'Tight Limit', 'phone' => '0700000001', 'credit_limit_cents' => 1000, 'balance_cents' => 0]);

        $component = Livewire::actingAs($attendant)
            ->test(Pos::class)
            ->call('addProduct', $this->product->id)
            ->call('selectCustomer', $customer->id)
            ->call('addPayment', 'credit')
            ->set('payments.0.amount', '65.00')
            ->call('completeSale');

        $this->assertTrue($component->get('showCreditOverrideModal'));
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_owner_pin_override_completes_an_over_limit_credit_sale(): void
    {
        $attendant = User::factory()->attendant()->create();
        $owner = User::factory()->owner()->create();
        $owner->setPin('1234');
        $this->stockUp($attendant);
        $customer = Customer::create(['name' => 'Tight Limit', 'phone' => '0700000001', 'credit_limit_cents' => 1000, 'balance_cents' => 0]);

        Livewire::actingAs($attendant)
            ->test(Pos::class)
            ->call('addProduct', $this->product->id)
            ->call('selectCustomer', $customer->id)
            ->call('addPayment', 'credit')
            ->set('payments.0.amount', '65.00')
            ->call('completeSale')
            ->set('overridePin', '1234')
            ->call('confirmCreditOverride');

        $this->assertDatabaseHas('sales', ['status' => Sale::STATUS_COMPLETED]);
        $this->assertSame(6500, $customer->fresh()->balance_cents);
    }

    public function test_wrong_pin_does_not_override_credit_limit(): void
    {
        $attendant = User::factory()->attendant()->create();
        $owner = User::factory()->owner()->create();
        $owner->setPin('1234');
        $this->stockUp($attendant);
        $customer = Customer::create(['name' => 'Tight Limit', 'phone' => '0700000001', 'credit_limit_cents' => 1000, 'balance_cents' => 0]);

        Livewire::actingAs($attendant)
            ->test(Pos::class)
            ->call('addProduct', $this->product->id)
            ->call('selectCustomer', $customer->id)
            ->call('addPayment', 'credit')
            ->set('payments.0.amount', '65.00')
            ->call('completeSale')
            ->set('overridePin', '9999')
            ->call('confirmCreditOverride')
            ->assertHasErrors('overridePin');

        $this->assertDatabaseCount('sales', 0);
    }

    public function test_insufficient_stock_shows_error_and_creates_no_sale(): void
    {
        $attendant = User::factory()->attendant()->create();
        $this->stockUp($attendant)->update(['quantity_remaining' => 2]);

        Livewire::actingAs($attendant)
            ->test(Pos::class)
            ->call('addProduct', $this->product->id)
            ->set('cart.1.quantity', '10')
            ->call('addPayment', 'cash')
            ->set('payments.0.amount', '650.00')
            ->call('completeSale')
            ->assertHasErrors('cart');

        $this->assertDatabaseCount('sales', 0);
    }
}
