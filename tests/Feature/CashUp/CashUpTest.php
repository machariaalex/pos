<?php

namespace Tests\Feature\CashUp;

use App\Livewire\CashUp\Index as CashUpIndex;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CashUpTest extends TestCase
{
    use RefreshDatabase;

    public function test_any_role_can_access_cash_up(): void
    {
        $attendant = User::factory()->attendant()->create();

        $this->actingAs($attendant)->get(route('cash-up.index'))->assertOk();
    }

    public function test_attendant_can_declare_their_drawer(): void
    {
        $attendant = User::factory()->attendant()->create();

        Livewire::actingAs($attendant)
            ->test(CashUpIndex::class)
            ->set('declaredAmount', '5000')
            ->call('declare');

        $this->assertDatabaseHas('cash_ups', [
            'user_id' => $attendant->id,
            'declared_cash_cents' => 500000,
        ]);
    }

    public function test_redeclaring_updates_the_existing_record(): void
    {
        $attendant = User::factory()->attendant()->create();

        Livewire::actingAs($attendant)
            ->test(CashUpIndex::class)
            ->set('declaredAmount', '5000')
            ->call('declare');

        Livewire::actingAs($attendant)
            ->test(CashUpIndex::class)
            ->set('declaredAmount', '6000')
            ->call('declare');

        $this->assertDatabaseCount('cash_ups', 1);
        $this->assertDatabaseHas('cash_ups', ['declared_cash_cents' => 600000]);
    }

    public function test_attendant_only_sees_their_own_history(): void
    {
        $alice = User::factory()->attendant()->create();
        $bob = User::factory()->attendant()->create();

        Livewire::actingAs($alice)->test(CashUpIndex::class)->set('declaredAmount', '1000')->call('declare');
        Livewire::actingAs($bob)->test(CashUpIndex::class)->set('declaredAmount', '2000')->call('declare');

        $response = $this->actingAs($alice)->get(route('cash-up.index'));

        $response->assertSee('1,000.00');
        $response->assertDontSee('2,000.00');
    }

    public function test_manager_sees_all_attendants_history(): void
    {
        $manager = User::factory()->manager()->create();
        $alice = User::factory()->attendant()->create();

        Livewire::actingAs($alice)->test(CashUpIndex::class)->set('declaredAmount', '1000')->call('declare');

        $response = $this->actingAs($manager)->get(route('cash-up.index'));

        $response->assertSee('1,000.00');
        $response->assertSee($alice->name);
    }
}
