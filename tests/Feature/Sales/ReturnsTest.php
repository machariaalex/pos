<?php

namespace Tests\Feature\Sales;

use App\Actions\Sales\CompleteSale;
use App\Livewire\Sales\Returns as SalesReturns;
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

class ReturnsTest extends TestCase
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
            [['product_id' => $this->product->id, 'quantity' => '4', 'unit_price_cents' => 6500]],
            null,
            [['method' => Payment::METHOD_CASH, 'amount_cents' => 26000]],
            $this->cashier,
        );
    }

    public function test_returns_page_loads(): void
    {
        $this->actingAs($this->cashier)->get(route('sales.returns', $this->sale))->assertOk();
    }

    public function test_valid_return_with_approved_pin_restocks_and_redirects(): void
    {
        $manager = User::factory()->manager()->create();
        $manager->setPin('5678');
        $line = $this->sale->lines->first();

        Livewire::actingAs($this->cashier)
            ->test(SalesReturns::class, ['sale' => $this->sale])
            ->set("quantities.{$line->id}", '1')
            ->set('reason', 'Customer changed mind')
            ->set('pin', '5678')
            ->call('submit')
            ->assertRedirect(route('sales.receipt', $this->sale));

        $this->assertSame('47.000', (string) $this->batch->fresh()->quantity_remaining); // 46 + 1 back
        $this->assertDatabaseHas('sale_returns', ['sale_id' => $this->sale->id, 'total_refund_cents' => 6500]);
    }

    public function test_return_without_a_reason_fails_validation(): void
    {
        $manager = User::factory()->manager()->create();
        $manager->setPin('5678');
        $line = $this->sale->lines->first();

        Livewire::actingAs($this->cashier)
            ->test(SalesReturns::class, ['sale' => $this->sale])
            ->set("quantities.{$line->id}", '1')
            ->set('reason', '')
            ->set('pin', '5678')
            ->call('submit')
            ->assertHasErrors('reason');

        $this->assertDatabaseCount('sale_returns', 0);
    }

    public function test_return_with_invalid_pin_is_rejected(): void
    {
        $manager = User::factory()->manager()->create();
        $manager->setPin('5678');
        $line = $this->sale->lines->first();

        Livewire::actingAs($this->cashier)
            ->test(SalesReturns::class, ['sale' => $this->sale])
            ->set("quantities.{$line->id}", '1')
            ->set('reason', 'Testing bad pin')
            ->set('pin', '0000')
            ->call('submit')
            ->assertHasErrors('pin');

        $this->assertDatabaseCount('sale_returns', 0);
    }

    public function test_return_with_no_quantities_entered_shows_error(): void
    {
        $manager = User::factory()->manager()->create();
        $manager->setPin('5678');

        Livewire::actingAs($this->cashier)
            ->test(SalesReturns::class, ['sale' => $this->sale])
            ->set('reason', 'Nothing entered')
            ->set('pin', '5678')
            ->call('submit')
            ->assertHasErrors('quantities');

        $this->assertDatabaseCount('sale_returns', 0);
    }
}
