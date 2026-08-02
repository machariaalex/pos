<?php

namespace Tests\Feature\Customers;

use App\Livewire\Customers\Index as CustomersIndex;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendant_can_view_customers_list(): void
    {
        $attendant = User::factory()->attendant()->create();

        $this->actingAs($attendant)->get(route('customers.index'))->assertOk();
    }

    public function test_attendant_can_create_a_customer_without_a_credit_limit(): void
    {
        $attendant = User::factory()->attendant()->create();

        Livewire::actingAs($attendant)
            ->test(CustomersIndex::class)
            ->call('startCreate')
            ->set('name', 'New Farmer')
            ->set('phone', '0722000111')
            ->call('save');

        $this->assertDatabaseHas('customers', [
            'name' => 'New Farmer',
            'phone' => '0722000111',
            'credit_limit_cents' => null,
        ]);
    }

    public function test_attendant_cannot_force_a_credit_limit_even_by_setting_the_property_directly(): void
    {
        $attendant = User::factory()->attendant()->create();

        Livewire::actingAs($attendant)
            ->test(CustomersIndex::class)
            ->call('startCreate')
            ->set('name', 'Sneaky Limit')
            ->set('phone', '0722000111')
            ->set('hasCreditLimit', true)
            ->set('creditLimit', '50000')
            ->call('save');

        $this->assertDatabaseHas('customers', [
            'name' => 'Sneaky Limit',
            'credit_limit_cents' => null,
        ]);
    }

    public function test_manager_can_create_a_customer_with_a_credit_limit(): void
    {
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)
            ->test(CustomersIndex::class)
            ->call('startCreate')
            ->set('name', 'Trusted Farmer')
            ->set('phone', '0722000111')
            ->set('hasCreditLimit', true)
            ->set('creditLimit', '20000.00')
            ->call('save');

        $this->assertDatabaseHas('customers', [
            'name' => 'Trusted Farmer',
            'credit_limit_cents' => 2000000,
        ]);
    }

    public function test_invalid_phone_format_is_rejected(): void
    {
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)
            ->test(CustomersIndex::class)
            ->call('startCreate')
            ->set('name', 'Bad Phone')
            ->set('phone', '12345')
            ->call('save')
            ->assertHasErrors('phone');

        $this->assertDatabaseMissing('customers', ['name' => 'Bad Phone']);
    }

    public function test_valid_kenyan_phone_formats_are_accepted(): void
    {
        $manager = User::factory()->manager()->create();

        foreach (['0722334455', '0122334455', '+254722334455', '254722334455'] as $i => $phone) {
            Livewire::actingAs($manager)
                ->test(CustomersIndex::class)
                ->call('startCreate')
                ->set('name', "Farmer {$i}")
                ->set('phone', $phone)
                ->call('save')
                ->assertHasNoErrors();
        }

        $this->assertSame(4, Customer::count());
    }

    public function test_editing_a_customer_updates_their_details(): void
    {
        $manager = User::factory()->manager()->create();
        $customer = Customer::create(['name' => 'Old Name', 'phone' => '0700000000', 'balance_cents' => 0]);

        Livewire::actingAs($manager)
            ->test(CustomersIndex::class)
            ->call('startEdit', $customer->id)
            ->set('name', 'New Name')
            ->call('save');

        $this->assertSame('New Name', $customer->fresh()->name);
    }

    public function test_editing_a_customer_is_audit_logged(): void
    {
        $manager = User::factory()->manager()->create();
        $customer = Customer::create(['name' => 'Old Name', 'phone' => '0700000000', 'balance_cents' => 0]);

        Livewire::actingAs($manager)
            ->test(CustomersIndex::class)
            ->call('startEdit', $customer->id)
            ->set('name', 'New Name')
            ->call('save');

        $this->assertDatabaseHas('audit_logs', ['action' => 'customer.updated']);
    }
}
