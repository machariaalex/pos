<?php

namespace Tests\Feature\Inventory;

use App\Livewire\Inventory\Categories\Index;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendant_can_view_categories_list(): void
    {
        $attendant = User::factory()->attendant()->create();
        Category::create(['name' => 'Feeds']);

        $this->actingAs($attendant)->get(route('inventory.categories.index'))->assertOk();
    }

    public function test_manager_can_create_a_category(): void
    {
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)
            ->test(Index::class)
            ->call('startCreate')
            ->set('name', 'Seeds')
            ->call('save');

        $this->assertDatabaseHas('categories', ['name' => 'Seeds']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'category.created']);
    }

    public function test_attendant_cannot_create_a_category(): void
    {
        $attendant = User::factory()->attendant()->create();

        Livewire::actingAs($attendant)
            ->test(Index::class)
            ->set('name', 'Sneaky Category')
            ->call('save');

        $this->assertDatabaseMissing('categories', ['name' => 'Sneaky Category']);
    }

    public function test_manager_can_rename_a_category(): void
    {
        $manager = User::factory()->manager()->create();
        $category = Category::create(['name' => 'Feeds']);

        Livewire::actingAs($manager)
            ->test(Index::class)
            ->call('startEdit', $category->id)
            ->set('name', 'Animal Feeds')
            ->call('save');

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Animal Feeds']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'category.updated']);
    }

    public function test_creating_a_category_rejects_a_duplicate_name(): void
    {
        $manager = User::factory()->manager()->create();
        Category::create(['name' => 'Feeds']);

        Livewire::actingAs($manager)
            ->test(Index::class)
            ->call('startCreate')
            ->set('name', 'Feeds')
            ->call('save')
            ->assertHasErrors('name');
    }

    public function test_manager_can_delete_an_unused_category(): void
    {
        $manager = User::factory()->manager()->create();
        $category = Category::create(['name' => 'Unused']);

        Livewire::actingAs($manager)
            ->test(Index::class)
            ->call('delete', $category->id);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'category.deleted']);
    }

    public function test_cannot_delete_a_category_still_used_by_products(): void
    {
        $manager = User::factory()->manager()->create();
        $category = Category::create(['name' => 'Feeds']);
        Product::create([
            'category_id' => $category->id,
            'name' => 'Dairy Meal',
            'base_unit' => 'kg',
            'buying_price_cents' => 5200,
            'selling_price_cents' => 6500,
            'reorder_level' => 10,
        ]);

        Livewire::actingAs($manager)
            ->test(Index::class)
            ->call('delete', $category->id)
            ->assertHasErrors('delete');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
