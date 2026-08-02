<?php

namespace Tests\Feature\Auth;

use App\Livewire\Admin\Users\Index as UsersIndex;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_users_page(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->get(route('users.index'))->assertOk();
    }

    public function test_manager_cannot_view_users_page(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)->get(route('users.index'))->assertForbidden();
    }

    public function test_attendant_cannot_view_users_page(): void
    {
        $attendant = User::factory()->attendant()->create();

        $this->actingAs($attendant)->get(route('users.index'))->assertForbidden();
    }

    public function test_owner_can_create_a_new_user(): void
    {
        $owner = User::factory()->owner()->create();

        Livewire::actingAs($owner)
            ->test(UsersIndex::class)
            ->set('name', 'New Attendant')
            ->set('email', 'new.attendant@agrovet.test')
            ->set('phone', '0711000000')
            ->set('role', User::ROLE_ATTENDANT)
            ->set('password', 'a-strong-password')
            ->call('save');

        $this->assertDatabaseHas('users', [
            'email' => 'new.attendant@agrovet.test',
            'role' => User::ROLE_ATTENDANT,
        ]);
    }

    public function test_owner_can_deactivate_a_user(): void
    {
        $owner = User::factory()->owner()->create();
        $attendant = User::factory()->attendant()->create(['is_active' => true]);

        Livewire::actingAs($owner)
            ->test(UsersIndex::class)
            ->call('toggleActive', $attendant->id);

        $this->assertFalse($attendant->fresh()->is_active);
    }

    public function test_owner_cannot_deactivate_their_own_account(): void
    {
        $owner = User::factory()->owner()->create();

        Livewire::actingAs($owner)
            ->test(UsersIndex::class)
            ->call('toggleActive', $owner->id);

        $this->assertTrue($owner->fresh()->is_active);
    }

    public function test_setting_a_pin_allows_pin_verification(): void
    {
        $owner = User::factory()->owner()->create();

        Livewire::actingAs($owner)
            ->test(UsersIndex::class)
            ->set('name', 'New Manager')
            ->set('email', 'new.manager@agrovet.test')
            ->set('role', User::ROLE_MANAGER)
            ->set('password', 'a-strong-password')
            ->set('pin', '4321')
            ->call('save');

        $manager = User::where('email', 'new.manager@agrovet.test')->firstOrFail();

        $this->assertTrue($manager->verifyPin('4321'));
        $this->assertFalse($manager->verifyPin('0000'));
    }
}
