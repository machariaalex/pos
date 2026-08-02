<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use App\Models\CustomerPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerLedgerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->owner()->create();
        $this->customer = Customer::create([
            'name' => 'Test Farmer',
            'phone' => '0700000000',
            'credit_limit_cents' => 1000000, // KES 10,000
            'balance_cents' => 0,
        ]);
    }

    private function charge(int $amountCents): CustomerLedgerEntry
    {
        $this->customer->refresh();
        $newBalance = $this->customer->balance_cents + $amountCents;

        $entry = CustomerLedgerEntry::create([
            'customer_id' => $this->customer->id,
            'type' => CustomerLedgerEntry::TYPE_CHARGE,
            'amount_cents' => $amountCents,
            'running_balance_cents' => $newBalance,
            'created_by' => $this->user->id,
        ]);

        $this->customer->update(['balance_cents' => $newBalance]);

        return $entry;
    }

    private function pay(int $amountCents): CustomerLedgerEntry
    {
        $this->customer->refresh();
        $payment = CustomerPayment::create([
            'customer_id' => $this->customer->id,
            'amount_cents' => $amountCents,
            'method' => 'cash',
            'received_by' => $this->user->id,
        ]);

        $newBalance = $this->customer->balance_cents - $amountCents;

        $entry = CustomerLedgerEntry::create([
            'customer_id' => $this->customer->id,
            'type' => CustomerLedgerEntry::TYPE_PAYMENT,
            'amount_cents' => $amountCents,
            'customer_payment_id' => $payment->id,
            'running_balance_cents' => $newBalance,
            'created_by' => $this->user->id,
        ]);

        $this->customer->update(['balance_cents' => $newBalance]);

        return $entry;
    }

    public function test_running_balance_accumulates_across_charges_and_payments(): void
    {
        $this->charge(500000); // 5,000
        $this->charge(200000); // 2,000
        $this->pay(300000);    // -3,000

        $this->customer->refresh();

        $this->assertSame(400000, $this->customer->balance_cents); // 4,000 KES
    }

    public function test_each_ledger_entry_snapshots_the_correct_running_balance(): void
    {
        $first = $this->charge(500000);
        $second = $this->charge(150000);
        $third = $this->pay(400000);

        $this->assertSame(500000, $first->running_balance_cents);
        $this->assertSame(650000, $second->running_balance_cents);
        $this->assertSame(250000, $third->running_balance_cents);
    }

    public function test_customer_balance_column_matches_sum_of_ledger_entries(): void
    {
        $this->charge(800000);
        $this->charge(120000);
        $this->pay(500000);
        $this->pay(50000);

        $this->customer->refresh();

        $chargesTotal = $this->customer->ledgerEntries()
            ->where('type', CustomerLedgerEntry::TYPE_CHARGE)
            ->sum('amount_cents');
        $paymentsTotal = $this->customer->ledgerEntries()
            ->where('type', CustomerLedgerEntry::TYPE_PAYMENT)
            ->sum('amount_cents');

        $this->assertSame($chargesTotal - $paymentsTotal, $this->customer->balance_cents);
    }

    public function test_would_exceed_credit_limit_detects_over_limit_charge(): void
    {
        $this->charge(900000); // 9,000 of 10,000 limit

        $this->customer->refresh();

        $this->assertTrue($this->customer->wouldExceedCreditLimit(200000)); // +2,000 would breach
        $this->assertFalse($this->customer->wouldExceedCreditLimit(100000)); // +1,000 lands exactly at limit
    }

    public function test_customer_without_credit_limit_never_exceeds(): void
    {
        $customer = Customer::create([
            'name' => 'No Limit Farmer',
            'phone' => '0700000001',
            'credit_limit_cents' => null,
            'balance_cents' => 5000000,
        ]);

        $this->assertFalse($customer->wouldExceedCreditLimit(999999999));
    }
}
