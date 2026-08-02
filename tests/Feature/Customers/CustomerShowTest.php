<?php

namespace Tests\Feature\Customers;

use App\Livewire\Customers\Show;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_page_loads_and_shows_balance(): void
    {
        $attendant = User::factory()->attendant()->create();
        $customer = Customer::create(['name' => 'John', 'phone' => '0700000000', 'balance_cents' => 500000]);

        $response = $this->actingAs($attendant)->get(route('customers.show', $customer));

        $response->assertOk();
        $response->assertSee('5,000.00');
    }

    public function test_recording_a_cash_payment_reduces_balance(): void
    {
        $attendant = User::factory()->attendant()->create();
        $customer = Customer::create(['name' => 'John', 'phone' => '0700000000', 'balance_cents' => 500000]);

        Livewire::actingAs($attendant)
            ->test(Show::class, ['customer' => $customer])
            ->call('startPayment')
            ->set('amount', '2000.00')
            ->set('method', 'cash')
            ->call('recordPayment');

        $this->assertSame(300000, $customer->fresh()->balance_cents);
    }

    public function test_mpesa_payment_requires_a_transaction_code(): void
    {
        $attendant = User::factory()->attendant()->create();
        $customer = Customer::create(['name' => 'John', 'phone' => '0700000000', 'balance_cents' => 500000]);

        Livewire::actingAs($attendant)
            ->test(Show::class, ['customer' => $customer])
            ->call('startPayment')
            ->set('amount', '2000.00')
            ->set('method', 'mpesa')
            ->set('mpesaCode', '')
            ->call('recordPayment')
            ->assertHasErrors('mpesaCode');

        $this->assertSame(500000, $customer->fresh()->balance_cents);
    }

    public function test_payment_appears_in_the_ledger(): void
    {
        $attendant = User::factory()->attendant()->create();
        $customer = Customer::create(['name' => 'John', 'phone' => '0700000000', 'balance_cents' => 500000]);

        Livewire::actingAs($attendant)
            ->test(Show::class, ['customer' => $customer])
            ->call('startPayment')
            ->set('amount', '2000.00')
            ->set('method', 'mpesa')
            ->set('mpesaCode', 'ABC123')
            ->call('recordPayment');

        $this->assertDatabaseHas('customer_ledger_entries', [
            'customer_id' => $customer->id,
            'type' => 'payment',
            'amount_cents' => 200000,
        ]);
    }
}
