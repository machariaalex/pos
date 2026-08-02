<?php

namespace Tests\Unit\Actions\Customers;

use App\Actions\Customers\RecordCustomerPayment;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordCustomerPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_reduces_customer_balance(): void
    {
        $user = User::factory()->attendant()->create();
        $customer = Customer::create(['name' => 'John', 'phone' => '0700000000', 'balance_cents' => 500000]);

        (new RecordCustomerPayment)($customer, 200000, 'cash', null, $user);

        $this->assertSame(300000, $customer->fresh()->balance_cents);
    }

    public function test_payment_creates_a_customer_payment_record(): void
    {
        $user = User::factory()->attendant()->create();
        $customer = Customer::create(['name' => 'John', 'phone' => '0700000000', 'balance_cents' => 500000]);

        $payment = (new RecordCustomerPayment)($customer, 200000, 'mpesa', 'QAX999', $user, 'Harvest payment');

        $this->assertDatabaseHas('customer_payments', [
            'id' => $payment->id,
            'customer_id' => $customer->id,
            'amount_cents' => 200000,
            'method' => 'mpesa',
            'mpesa_code' => 'QAX999',
            'received_by' => $user->id,
        ]);
    }

    public function test_payment_creates_a_ledger_entry_with_correct_running_balance(): void
    {
        $user = User::factory()->attendant()->create();
        $customer = Customer::create(['name' => 'John', 'phone' => '0700000000', 'balance_cents' => 500000]);

        (new RecordCustomerPayment)($customer, 150000, 'cash', null, $user);

        $this->assertDatabaseHas('customer_ledger_entries', [
            'customer_id' => $customer->id,
            'type' => 'payment',
            'amount_cents' => 150000,
            'running_balance_cents' => 350000,
        ]);
    }

    public function test_multiple_payments_accumulate_correctly(): void
    {
        $user = User::factory()->attendant()->create();
        $customer = Customer::create(['name' => 'John', 'phone' => '0700000000', 'balance_cents' => 1000000]);

        (new RecordCustomerPayment)($customer, 300000, 'cash', null, $user);
        (new RecordCustomerPayment)($customer, 250000, 'mpesa', 'ABC123', $user);

        $this->assertSame(450000, $customer->fresh()->balance_cents);
        $this->assertSame(2, $customer->fresh()->ledgerEntries()->count());
    }

    public function test_payment_can_take_balance_negative_representing_customer_credit(): void
    {
        $user = User::factory()->attendant()->create();
        $customer = Customer::create(['name' => 'John', 'phone' => '0700000000', 'balance_cents' => 100000]);

        (new RecordCustomerPayment)($customer, 150000, 'cash', null, $user);

        $this->assertSame(-50000, $customer->fresh()->balance_cents);
    }

    public function test_payment_is_audit_logged(): void
    {
        $user = User::factory()->attendant()->create();
        $customer = Customer::create(['name' => 'John', 'phone' => '0700000000', 'balance_cents' => 500000]);

        (new RecordCustomerPayment)($customer, 200000, 'cash', null, $user);

        $this->assertDatabaseHas('audit_logs', ['action' => 'customer.payment_recorded', 'user_id' => $user->id]);
    }
}
