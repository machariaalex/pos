<?php

namespace Tests\Feature\Inventory;

use App\Livewire\Inventory\Products\Index as ProductsIndex;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create(['name' => 'Feeds']);
    }

    public function test_attendant_can_view_products_list(): void
    {
        $attendant = User::factory()->attendant()->create();

        $this->actingAs($attendant)->get(route('inventory.products.index'))->assertOk();
    }

    public function test_attendant_does_not_see_buying_price_in_product_list(): void
    {
        $attendant = User::factory()->attendant()->create();
        Product::create([
            'category_id' => $this->category->id,
            'name' => 'Dairy Meal',
            'base_unit' => 'kg',
            'buying_price_cents' => 5200,
            'selling_price_cents' => 6500,
            'reorder_level' => 10,
        ]);

        $response = $this->actingAs($attendant)->get(route('inventory.products.index'));

        $response->assertDontSee('52.00'); // buying price formatted
        $response->assertSee('65.00'); // selling price formatted
    }

    public function test_manager_sees_buying_price_in_product_list(): void
    {
        $manager = User::factory()->manager()->create();
        Product::create([
            'category_id' => $this->category->id,
            'name' => 'Dairy Meal',
            'base_unit' => 'kg',
            'buying_price_cents' => 5200,
            'selling_price_cents' => 6500,
            'reorder_level' => 10,
        ]);

        $response = $this->actingAs($manager)->get(route('inventory.products.index'));

        $response->assertSee('52.00');
        $response->assertSee('65.00');
    }

    public function test_attendant_cannot_create_a_product(): void
    {
        // Livewire routes AuthorizationException through Laravel's normal
        // exception handler (into an HTTP 403) rather than letting it
        // propagate as a raw PHP exception, so we assert on the outcome
        // (no product persisted) rather than catching the exception.
        $attendant = User::factory()->attendant()->create();

        Livewire::actingAs($attendant)
            ->test(ProductsIndex::class)
            ->set('name', 'Sneaky Product')
            ->set('categoryId', $this->category->id)
            ->set('baseUnit', 'kg')
            ->set('buyingPrice', '10')
            ->set('sellingPrice', '20')
            ->set('reorderLevel', '5')
            ->call('save');

        $this->assertDatabaseMissing('products', ['name' => 'Sneaky Product']);
    }

    public function test_manager_can_create_a_product(): void
    {
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)
            ->test(ProductsIndex::class)
            ->set('name', 'Broiler Starter')
            ->set('categoryId', $this->category->id)
            ->set('baseUnit', 'kg')
            ->set('buyingPrice', '58.00')
            ->set('sellingPrice', '72.00')
            ->set('reorderLevel', '75')
            ->call('save');

        $this->assertDatabaseHas('products', [
            'name' => 'Broiler Starter',
            'buying_price_cents' => 5800,
            'selling_price_cents' => 7200,
        ]);
    }

    public function test_editing_a_product_price_is_audit_logged_as_price_changed(): void
    {
        $manager = User::factory()->manager()->create();
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Dairy Meal',
            'base_unit' => 'kg',
            'buying_price_cents' => 5200,
            'selling_price_cents' => 6500,
            'reorder_level' => 10,
        ]);

        Livewire::actingAs($manager)
            ->test(ProductsIndex::class)
            ->call('startEdit', $product->id)
            ->set('sellingPrice', '70.00')
            ->call('save');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'selling_price_cents' => 7000]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'product.price_changed']);
    }

    public function test_editing_a_product_without_price_change_logs_generic_update(): void
    {
        $manager = User::factory()->manager()->create();
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Dairy Meal',
            'base_unit' => 'kg',
            'buying_price_cents' => 5200,
            'selling_price_cents' => 6500,
            'reorder_level' => 10,
        ]);

        Livewire::actingAs($manager)
            ->test(ProductsIndex::class)
            ->call('startEdit', $product->id)
            ->set('name', 'Dairy Meal (Premium)')
            ->call('save');

        $this->assertDatabaseHas('audit_logs', ['action' => 'product.updated']);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'product.price_changed']);
    }

    public function test_manager_can_quick_add_a_category_from_the_product_form(): void
    {
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)
            ->test(ProductsIndex::class)
            ->call('startCreate')
            ->set('newCategoryName', 'Equipment')
            ->call('addCategory')
            ->assertHasNoErrors();

        $category = Category::where('name', 'Equipment')->first();
        $this->assertNotNull($category);
        $this->assertDatabaseHas('audit_logs', ['action' => 'category.created']);
    }

    public function test_quick_add_category_selects_it_for_the_product_being_created(): void
    {
        $manager = User::factory()->manager()->create();

        $component = Livewire::actingAs($manager)
            ->test(ProductsIndex::class)
            ->call('startCreate')
            ->set('newCategoryName', 'Equipment')
            ->call('addCategory');

        $category = Category::where('name', 'Equipment')->firstOrFail();
        $component->assertSet('categoryId', $category->id);
    }

    public function test_quick_add_category_rejects_a_duplicate_name(): void
    {
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)
            ->test(ProductsIndex::class)
            ->call('startCreate')
            ->set('newCategoryName', $this->category->name)
            ->call('addCategory')
            ->assertHasErrors('newCategoryName');
    }

    public function test_attendant_cannot_quick_add_a_category(): void
    {
        $attendant = User::factory()->attendant()->create();

        Livewire::actingAs($attendant)
            ->test(ProductsIndex::class)
            ->set('newCategoryName', 'Sneaky Category')
            ->call('addCategory');

        $this->assertDatabaseMissing('categories', ['name' => 'Sneaky Category']);
    }

    public function test_product_search_filters_by_name_and_barcode(): void
    {
        $manager = User::factory()->manager()->create();
        Product::create([
            'category_id' => $this->category->id, 'name' => 'Dairy Meal', 'barcode' => 'AGV000001',
            'base_unit' => 'kg', 'buying_price_cents' => 5200, 'selling_price_cents' => 6500, 'reorder_level' => 10,
        ]);
        Product::create([
            'category_id' => $this->category->id, 'name' => 'Layers Mash', 'barcode' => 'AGV000002',
            'base_unit' => 'kg', 'buying_price_cents' => 5000, 'selling_price_cents' => 6200, 'reorder_level' => 10,
        ]);

        Livewire::actingAs($manager)
            ->test(ProductsIndex::class)
            ->set('search', 'Dairy')
            ->assertSee('Dairy Meal')
            ->assertDontSee('Layers Mash');

        Livewire::actingAs($manager)
            ->test(ProductsIndex::class)
            ->set('search', 'AGV000002')
            ->assertSee('Layers Mash')
            ->assertDontSee('Dairy Meal');
    }

    public function test_manager_can_create_a_product_with_a_bulk_pack_price(): void
    {
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)
            ->test(ProductsIndex::class)
            ->call('startCreate')
            ->set('name', 'Chick Mash')
            ->set('categoryId', $this->category->id)
            ->set('baseUnit', 'pcs')
            ->set('hasSellingUnit', true)
            ->set('sellingUnit', 'kg')
            ->set('unitsPerBase', '50')
            ->set('hasBulkPack', true)
            ->set('packSize', '50')
            ->set('packPrice', '2500')
            ->set('buyingPrice', '40')
            ->set('sellingPrice', '60')
            ->set('reorderLevel', '1')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', [
            'name' => 'Chick Mash',
            'pack_size' => 50,
            'pack_price_cents' => 250000,
        ]);
    }

    public function test_bulk_pack_fields_are_ignored_when_the_toggle_is_off(): void
    {
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)
            ->test(ProductsIndex::class)
            ->call('startCreate')
            ->set('name', 'Dairy Meal')
            ->set('categoryId', $this->category->id)
            ->set('baseUnit', 'kg')
            ->set('hasBulkPack', false)
            ->set('packSize', '50')
            ->set('packPrice', '2500')
            ->set('buyingPrice', '40')
            ->set('sellingPrice', '60')
            ->set('reorderLevel', '1')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', [
            'name' => 'Dairy Meal',
            'pack_size' => null,
            'pack_price_cents' => null,
        ]);
    }

    public function test_bulk_pack_works_without_a_selling_unit_configured(): void
    {
        // A product sold and stocked purely in kg (no unit conversion) can
        // still offer bulk pricing — the toggle doesn't require hasSellingUnit.
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)
            ->test(ProductsIndex::class)
            ->call('startCreate')
            ->set('name', 'Chick Mash')
            ->set('categoryId', $this->category->id)
            ->set('baseUnit', 'kg')
            ->set('hasSellingUnit', false)
            ->set('hasBulkPack', true)
            ->set('packSize', '50')
            ->set('packPrice', '5000')
            ->set('buyingPrice', '40')
            ->set('sellingPrice', '60')
            ->set('reorderLevel', '1')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', [
            'name' => 'Chick Mash',
            'selling_unit' => null,
            'pack_size' => 50,
            'pack_price_cents' => 500000,
        ]);
    }
}
