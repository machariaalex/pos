<?php

namespace Tests\Feature\Sales;

use App\Actions\Sales\CompleteSale;
use App\Livewire\Sales\Receipt;
use App\Models\Batch;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class ReceiptTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private Batch $batch;

    private User $cashier;

    private Sale $sale;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create(['name' => 'Feeds']);
        $this->cashier = User::factory()->attendant()->create();
        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Dairy Meal',
            'base_unit' => 'kg',
            'buying_price_cents' => 5200,
            'selling_price_cents' => 6500,
            'reorder_level' => 10,
        ]);
        $this->batch = Batch::create([
            'product_id' => $this->product->id, 'batch_number' => 'B1', 'expiry_date' => Carbon::now()->addMonths(6),
            'quantity_received' => 50, 'quantity_remaining' => 50,
            'buying_price_cents' => 5200, 'received_at' => Carbon::now(), 'created_by' => $this->cashier->id,
        ]);

        $this->sale = app(CompleteSale::class)(
            [['product_id' => $this->product->id, 'quantity' => '2', 'unit_price_cents' => 6500]],
            null,
            [['method' => Payment::METHOD_CASH, 'amount_cents' => 13000]],
            $this->cashier,
        );
    }

    public function test_receipt_page_loads_for_any_role(): void
    {
        $this->actingAs($this->cashier)->get(route('sales.receipt', $this->sale))->assertOk();
    }

    public function test_receipt_shows_sale_number_and_total(): void
    {
        $response = $this->actingAs($this->cashier)->get(route('sales.receipt', $this->sale));

        $response->assertSee($this->sale->sale_number);
        $response->assertSee('130.00');
    }

    public function test_attendant_can_initiate_a_void_but_needs_a_valid_owner_pin(): void
    {
        $owner = User::factory()->owner()->create();
        $owner->setPin('1234');

        Livewire::actingAs($this->cashier)
            ->test(Receipt::class, ['sale' => $this->sale])
            ->call('startVoid')
            ->set('voidReason', 'Wrong item rung up')
            ->set('voidPin', '0000')
            ->call('confirmVoid')
            ->assertHasErrors('voidPin');

        $this->assertSame(Sale::STATUS_COMPLETED, $this->sale->fresh()->status);
    }

    public function test_valid_owner_pin_voids_the_sale_and_restocks(): void
    {
        $owner = User::factory()->owner()->create();
        $owner->setPin('1234');

        Livewire::actingAs($this->cashier)
            ->test(Receipt::class, ['sale' => $this->sale])
            ->call('startVoid')
            ->set('voidReason', 'Wrong item rung up')
            ->set('voidPin', '1234')
            ->call('confirmVoid');

        $this->sale->refresh();
        $this->assertSame(Sale::STATUS_VOIDED, $this->sale->status);
        $this->assertSame($owner->id, $this->sale->approved_by);
        $this->assertSame('50.000', (string) $this->batch->fresh()->quantity_remaining);
    }

    public function test_a_managers_pin_is_not_sufficient_to_void_a_sale(): void
    {
        // Void is stricter than a return/refund — the spec calls for the
        // owner's PIN specifically, unlike returns which allow a manager's.
        $manager = User::factory()->manager()->create();
        $manager->setPin('5678');

        Livewire::actingAs($this->cashier)
            ->test(Receipt::class, ['sale' => $this->sale])
            ->call('startVoid')
            ->set('voidReason', 'Wrong item rung up')
            ->set('voidPin', '5678')
            ->call('confirmVoid')
            ->assertHasErrors('voidPin');

        $this->assertSame(Sale::STATUS_COMPLETED, $this->sale->fresh()->status);
    }

    public function test_too_many_wrong_pin_attempts_are_throttled(): void
    {
        $owner = User::factory()->owner()->create();
        $owner->setPin('1234');

        $component = Livewire::actingAs($this->cashier)
            ->test(Receipt::class, ['sale' => $this->sale])
            ->call('startVoid')
            ->set('voidReason', 'Wrong item rung up');

        for ($i = 0; $i < 5; $i++) {
            $component->set('voidPin', '0000')->call('confirmVoid');
        }

        // The 6th attempt should be throttled even with the correct PIN.
        $component->set('voidPin', '1234')->call('confirmVoid')->assertHasErrors('voidPin');

        $this->assertSame(Sale::STATUS_COMPLETED, $this->sale->fresh()->status);
    }

    public function test_voided_sale_receipt_is_clearly_marked_even_if_printed_directly(): void
    {
        // The print button is hidden for voided sales, but nothing stops a
        // manual Ctrl+P — the printable content itself must be marked.
        $this->sale->update(['status' => Sale::STATUS_VOIDED]);

        $response = $this->actingAs($this->cashier)->get(route('sales.receipt', $this->sale));

        $response->assertSee('VOIDED');
    }

    public function test_completed_sale_receipt_has_no_voided_marking(): void
    {
        $response = $this->actingAs($this->cashier)->get(route('sales.receipt', $this->sale));

        $response->assertDontSee('VOIDED');
    }

    public function test_void_without_a_reason_fails_validation(): void
    {
        $owner = User::factory()->owner()->create();
        $owner->setPin('1234');

        Livewire::actingAs($this->cashier)
            ->test(Receipt::class, ['sale' => $this->sale])
            ->call('startVoid')
            ->set('voidReason', '')
            ->set('voidPin', '1234')
            ->call('confirmVoid')
            ->assertHasErrors('voidReason');

        $this->assertSame(Sale::STATUS_COMPLETED, $this->sale->fresh()->status);
    }
}
