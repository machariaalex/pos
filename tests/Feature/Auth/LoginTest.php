<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->attendant()->create([
            'email' => 'attendant@agrovet.test',
            'password' => 'correct-password',
        ]);

        Livewire::test(Login::class)
            ->set('email', 'attendant@agrovet.test')
            ->set('password', 'correct-password')
            ->call('login')
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_login_with_mismatched_email_case_and_whitespace(): void
    {
        $user = User::factory()->attendant()->create([
            'email' => 'attendant@agrovet.test',
            'password' => 'correct-password',
        ]);

        Livewire::test(Login::class)
            ->set('email', '  Attendant@Agrovet.TEST  ')
            ->set('password', 'correct-password')
            ->call('login')
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->attendant()->create([
            'email' => 'attendant@agrovet.test',
            'password' => 'correct-password',
        ]);

        Livewire::test(Login::class)
            ->set('email', 'attendant@agrovet.test')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_deactivated_user_cannot_login(): void
    {
        User::factory()->attendant()->inactive()->create([
            'email' => 'gone@agrovet.test',
            'password' => 'correct-password',
        ]);

        Livewire::test(Login::class)
            ->set('email', 'gone@agrovet.test')
            ->set('password', 'correct-password')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_failed_login_is_audit_logged(): void
    {
        User::factory()->attendant()->create([
            'email' => 'attendant@agrovet.test',
            'password' => 'correct-password',
        ]);

        Livewire::test(Login::class)
            ->set('email', 'attendant@agrovet.test')
            ->set('password', 'wrong-password')
            ->call('login');

        $this->assertDatabaseHas('audit_logs', ['action' => 'login.failed']);
    }

    public function test_successful_login_is_audit_logged(): void
    {
        User::factory()->attendant()->create([
            'email' => 'attendant@agrovet.test',
            'password' => 'correct-password',
        ]);

        Livewire::test(Login::class)
            ->set('email', 'attendant@agrovet.test')
            ->set('password', 'correct-password')
            ->call('login');

        $this->assertDatabaseHas('audit_logs', ['action' => 'login.success']);
    }

    public function test_active_deactivated_mid_session_is_logged_out_on_next_request(): void
    {
        $user = User::factory()->attendant()->create();

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertOk();

        $user->update(['is_active' => false]);

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
