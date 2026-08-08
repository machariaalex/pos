<?php

namespace Tests\Feature\Inventory;

use App\Livewire\Inventory\ReceiveStock\Index;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class ReceiveStockTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

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

    public function test_manager_can_search_select_and_receive_stock_for_a_product(): void
    {
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)
            ->test(Index::class)
            ->set('search', 'Dairy')
            ->assertSee('Dairy Meal')
            ->call('selectProduct', $this->product->id)
            ->set('batchNumber', 'B-500')
            ->set('quantityReceived', '250')
            ->set('buyingPrice', '52.00')
            ->set('receivedAt', Carbon::now()->toDateString())
            ->call('receive');

        $this->assertDatabaseHas('batches', [
            'product_id' => $this->product->id,
            'batch_number' => 'B-500',
            'quantity_received' => 250,
            'quantity_remaining' => 250,
        ]);
    }

    public function test_selecting_a_product_prefills_its_current_buying_price(): void
    {
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)
            ->test(Index::class)
            ->call('selectProduct', $this->product->id)
            ->assertSet('buyingPrice', '52.00');
    }

    public function test_attendant_cannot_access_receive_stock(): void
    {
        $attendant = User::factory()->attendant()->create();

        $this->actingAs($attendant)->get(route('inventory.receive-stock'))->assertForbidden();
    }

    public function test_receiving_without_a_selected_product_does_nothing(): void
    {
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)
            ->test(Index::class)
            ->set('quantityReceived', '250')
            ->set('buyingPrice', '52.00')
            ->set('receivedAt', Carbon::now()->toDateString())
            ->call('receive');

        $this->assertDatabaseCount('batches', 0);
    }

    public function test_visiting_with_a_product_query_param_preselects_it(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get(route('inventory.receive-stock', ['product' => $this->product->id]))
            ->assertOk()
            ->assertSee('Dairy Meal');
    }

    public function test_manager_can_quick_add_a_supplier_and_it_gets_selected(): void
    {
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)
            ->test(Index::class)
            ->call('selectProduct', $this->product->id)
            ->set('newSupplierName', 'Kilimo Feeds Ltd')
            ->set('newSupplierPhone', '0712345678')
            ->call('addSupplier')
            ->assertHasNoErrors();

        $supplier = Supplier::where('name', 'Kilimo Feeds Ltd')->first();
        $this->assertNotNull($supplier);
        $this->assertSame('0712345678', $supplier->phone);
    }

    public function test_selecting_an_existing_supplier_by_search(): void
    {
        $manager = User::factory()->manager()->create();
        $supplier = Supplier::create(['name' => 'Agro Supplies Co', 'phone' => '0700111222']);

        Livewire::actingAs($manager)
            ->test(Index::class)
            ->call('selectProduct', $this->product->id)
            ->set('supplierSearch', 'Agro')
            ->assertSee('Agro Supplies Co')
            ->call('selectSupplier', $supplier->id)
            ->assertSet('supplierSearch', '');
    }

    public function test_supplier_is_saved_on_the_received_batch(): void
    {
        $manager = User::factory()->manager()->create();
        $supplier = Supplier::create(['name' => 'Agro Supplies Co']);

        Livewire::actingAs($manager)
            ->test(Index::class)
            ->call('selectProduct', $this->product->id)
            ->call('selectSupplier', $supplier->id)
            ->set('quantityReceived', '100')
            ->set('buyingPrice', '52.00')
            ->set('receivedAt', Carbon::now()->toDateString())
            ->call('receive');

        $this->assertDatabaseHas('batches', [
            'product_id' => $this->product->id,
            'supplier_id' => $supplier->id,
            'quantity_received' => 100,
        ]);
    }

    public function test_batch_can_be_received_without_a_supplier(): void
    {
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)
            ->test(Index::class)
            ->call('selectProduct', $this->product->id)
            ->set('quantityReceived', '100')
            ->set('buyingPrice', '52.00')
            ->set('receivedAt', Carbon::now()->toDateString())
            ->call('receive');

        $this->assertDatabaseHas('batches', [
            'product_id' => $this->product->id,
            'supplier_id' => null,
        ]);
    }

    public function test_attendant_granted_adjust_stock_but_not_view_buying_price_cannot_see_or_set_buying_price(): void
    {
        $attendant = User::factory()->attendant()->create(['permissions' => ['adjust-stock']]);

        Livewire::actingAs($attendant)
            ->test(Index::class)
            ->call('selectProduct', $this->product->id)
            ->assertDontSee('Buying price per')
            // Even if a malicious client tampers with the hidden field's
            // value, the server must ignore it and carry the product's
            // actual last-known cost forward instead.
            ->set('buyingPrice', '999999.00')
            ->set('quantityReceived', '100')
            ->set('receivedAt', Carbon::now()->toDateString())
            ->call('receive');

        $this->assertDatabaseHas('batches', [
            'product_id' => $this->product->id,
            'buying_price_cents' => $this->product->buying_price_cents,
        ]);
        $this->assertDatabaseMissing('batches', ['buying_price_cents' => 99999900]);
    }
}
