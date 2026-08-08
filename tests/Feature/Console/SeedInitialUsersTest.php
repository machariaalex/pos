<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SeedInitialUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_demo_users_on_an_empty_database(): void
    {
        $this->assertDatabaseCount('users', 0);

        Artisan::call('app:seed-initial-users');

        $this->assertDatabaseHas('users', ['email' => 'owner@agrovet.test']);
        $this->assertDatabaseHas('users', ['email' => 'manager@agrovet.test']);
        $this->assertDatabaseHas('users', ['email' => 'attendant@agrovet.test']);
    }

    public function test_does_not_recreate_the_demo_owner_after_it_has_been_renamed(): void
    {
        // Reproduces the real incident: the seeded owner's email/password
        // were changed to real ones post-deploy, but every subsequent
        // deploy re-ran the seeder — which, keyed on the old email via
        // firstOrCreate, silently created a brand-new "owner@agrovet.test"
        // / password "password" account with live Owner access.
        User::factory()->owner()->create([
            'name' => 'Admin Waingo',
            'email' => 'waingofarmagrovet@gmail.com',
        ]);

        Artisan::call('app:seed-initial-users');

        $this->assertDatabaseMissing('users', ['email' => 'owner@agrovet.test']);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_does_not_touch_an_already_seeded_database(): void
    {
        Artisan::call('app:seed-initial-users');
        $this->assertDatabaseCount('users', 3);

        // A second run (e.g. the next deploy) must be a no-op.
        Artisan::call('app:seed-initial-users');
        $this->assertDatabaseCount('users', 3);
    }
}
