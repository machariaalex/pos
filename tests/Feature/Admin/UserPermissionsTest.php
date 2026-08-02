<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Users\Index as UsersIndex;
use App\Livewire\Inventory\Products\Index as ProductsIndex;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_grant_a_permission_to_an_attendant(): void
    {
        $owner = User::factory()->owner()->create();
        $attendant = User::factory()->attendant()->create();

        Livewire::actingAs($owner)
            ->test(UsersIndex::class)
            ->call('startEdit', $attendant->id)
            ->set('permissions', ['edit-price'])
            ->call('save');

        $this->assertSame(['edit-price'], $attendant->fresh()->permissions);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.permissions_updated']);
    }

    public function test_granted_permission_lets_an_attendant_create_a_product(): void
    {
        $attendant = User::factory()->attendant()->create(['permissions' => ['edit-price', 'view-buying-price']]);
        $category = Category::create(['name' => 'Feeds']);

        Livewire::actingAs($attendant)
            ->test(ProductsIndex::class)
            ->set('name', 'Broiler Starter')
            ->set('categoryId', $category->id)
            ->set('baseUnit', 'kg')
            ->set('buyingPrice', '58.00')
            ->set('sellingPrice', '72.00')
            ->set('reorderLevel', '5')
            ->call('save');

        $this->assertDatabaseHas('products', [
            'name' => 'Broiler Starter',
            'buying_price_cents' => 5800,
        ]);
    }

    public function test_attendant_without_the_permission_still_cannot_create_a_product(): void
    {
        // See ProductManagementTest::test_attendant_cannot_create_a_product
        // for why we assert on outcome rather than catching the exception.
        $attendant = User::factory()->attendant()->create();
        $category = Category::create(['name' => 'Feeds']);

        Livewire::actingAs($attendant)
            ->test(ProductsIndex::class)
            ->set('name', 'Sneaky Product')
            ->set('categoryId', $category->id)
            ->set('baseUnit', 'kg')
            ->set('buyingPrice', '10')
            ->set('sellingPrice', '20')
            ->set('reorderLevel', '5')
            ->call('save');

        $this->assertDatabaseMissing('products', ['name' => 'Sneaky Product']);
    }

    public function test_owner_can_revoke_a_previously_granted_permission(): void
    {
        $owner = User::factory()->owner()->create();
        $attendant = User::factory()->attendant()->create(['permissions' => ['edit-price']]);

        Livewire::actingAs($owner)
            ->test(UsersIndex::class)
            ->call('startEdit', $attendant->id)
            ->set('permissions', [])
            ->call('save');

        $this->assertSame([], $attendant->fresh()->permissions);

        $category = Category::create(['name' => 'Feeds']);

        Livewire::actingAs($attendant->fresh())
            ->test(ProductsIndex::class)
            ->set('name', 'Blocked Again')
            ->set('categoryId', $category->id)
            ->set('baseUnit', 'kg')
            ->set('sellingPrice', '20')
            ->set('reorderLevel', '5')
            ->call('save');

        $this->assertDatabaseMissing('products', ['name' => 'Blocked Again']);
    }

    public function test_attendant_granted_edit_price_but_not_view_buying_price_cannot_see_or_set_buying_price(): void
    {
        $attendant = User::factory()->attendant()->create(['permissions' => ['edit-price']]);
        $category = Category::create(['name' => 'Feeds']);

        Livewire::actingAs($attendant)
            ->test(ProductsIndex::class)
            ->call('startCreate')
            ->assertDontSee('Buying price')
            ->set('name', 'No Cost Visibility')
            ->set('categoryId', $category->id)
            ->set('baseUnit', 'kg')
            ->set('sellingPrice', '20')
            ->set('reorderLevel', '5')
            ->call('save');

        $this->assertDatabaseHas('products', [
            'name' => 'No Cost Visibility',
            'buying_price_cents' => 0,
        ]);
    }

    public function test_saving_a_user_as_owner_role_never_persists_permissions(): void
    {
        $owner = User::factory()->owner()->create();

        Livewire::actingAs($owner)
            ->test(UsersIndex::class)
            ->set('name', 'Second Owner')
            ->set('email', 'second.owner@agrovet.test')
            ->set('role', User::ROLE_OWNER)
            ->set('password', 'a-strong-password')
            ->set('permissions', ['edit-price'])
            ->call('save');

        $newOwner = User::where('email', 'second.owner@agrovet.test')->firstOrFail();
        $this->assertSame([], $newOwner->permissions ?? []);
    }
}
