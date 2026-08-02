<?php

namespace Tests\Unit\Actions\Customers;

use App\Actions\Customers\ComputeDebtAging;
use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ComputeDebtAgingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->owner()->create();
        $this->customer = Customer::create(['name' => 'John', 'phone' => '0700000000', 'balance_cents' => 0]);
    }

    /**
     * created_at is deliberately not mass-assignable on the model (a ledger
     * shouldn't let callers backdate entries through normal create()), so
     * tests that need to simulate ledger history set it directly and save.
     */
    private function entry(string $type, int $amountCents, int $daysAgo): CustomerLedgerEntry
    {
        $entry = new CustomerLedgerEntry([
            'customer_id' => $this->customer->id,
            'type' => $type,
            'amount_cents' => $amountCents,
            'running_balance_cents' => 0, // not exercised by this action, fine to leave inert
            'created_by' => $this->user->id,
        ]);
        $entry->created_at = Carbon::today()->subDays($daysAgo);
        $entry->save();

        return $entry;
    }

    private function charge(int $amountCents, int $daysAgo): CustomerLedgerEntry
    {
        return $this->entry(CustomerLedgerEntry::TYPE_CHARGE, $amountCents, $daysAgo);
    }

    private function payment(int $amountCents, int $daysAgo): CustomerLedgerEntry
    {
        return $this->entry(CustomerLedgerEntry::TYPE_PAYMENT, $amountCents, $daysAgo);
    }

    public function test_single_recent_charge_lands_in_current_bucket(): void
    {
        $this->charge(500000, 5);

        $buckets = (new ComputeDebtAging)($this->customer);

        $this->assertSame(500000, $buckets['current']);
        $this->assertSame(0, $buckets['days_30']);
        $this->assertSame(0, $buckets['days_60']);
        $this->assertSame(0, $buckets['days_90_plus']);
    }

    public function test_charge_45_days_old_lands_in_days_30_bucket(): void
    {
        $this->charge(500000, 45);

        $buckets = (new ComputeDebtAging)($this->customer);

        $this->assertSame(500000, $buckets['days_30']);
    }

    public function test_charge_75_days_old_lands_in_days_60_bucket(): void
    {
        $this->charge(500000, 75);

        $buckets = (new ComputeDebtAging)($this->customer);

        $this->assertSame(500000, $buckets['days_60']);
    }

    public function test_charge_over_90_days_old_lands_in_days_90_plus_bucket(): void
    {
        $this->charge(500000, 120);

        $buckets = (new ComputeDebtAging)($this->customer);

        $this->assertSame(500000, $buckets['days_90_plus']);
    }

    public function test_boundary_exactly_30_days_is_current(): void
    {
        $this->charge(500000, 30);

        $buckets = (new ComputeDebtAging)($this->customer);

        $this->assertSame(500000, $buckets['current']);
    }

    public function test_boundary_exactly_31_days_is_days_30(): void
    {
        $this->charge(500000, 31);

        $buckets = (new ComputeDebtAging)($this->customer);

        $this->assertSame(500000, $buckets['days_30']);
    }

    public function test_payment_fully_settles_a_single_charge(): void
    {
        $this->charge(500000, 45);
        $this->payment(500000, 10);

        $buckets = (new ComputeDebtAging)($this->customer);

        $this->assertSame(0, array_sum($buckets));
    }

    public function test_payment_partially_settles_the_oldest_charge_first(): void
    {
        // Two charges: an old one and a recent one. A payment smaller than
        // the old charge should reduce the OLD charge, not the new one —
        // that's the whole point of FIFO aging.
        $this->charge(500000, 75); // old, days_60 bucket
        $this->charge(300000, 5);  // recent, current bucket
        $this->payment(200000, 2); // pays down part of the old charge

        $buckets = (new ComputeDebtAging)($this->customer);

        $this->assertSame(300000, $buckets['days_60']); // 500000 - 200000
        $this->assertSame(300000, $buckets['current']); // untouched
    }

    public function test_payment_larger_than_oldest_charge_spills_into_next_charge(): void
    {
        $this->charge(200000, 75); // old
        $this->charge(500000, 5);  // recent
        $this->payment(300000, 2); // pays off the old charge (200000) then eats 100000 of the recent one

        $buckets = (new ComputeDebtAging)($this->customer);

        $this->assertSame(0, $buckets['days_60']);
        $this->assertSame(400000, $buckets['current']); // 500000 - 100000
    }

    public function test_customer_can_owe_across_multiple_buckets_simultaneously(): void
    {
        $this->charge(100000, 10);  // current
        $this->charge(100000, 45);  // days_30
        $this->charge(100000, 75);  // days_60
        $this->charge(100000, 120); // days_90_plus

        $buckets = (new ComputeDebtAging)($this->customer);

        $this->assertSame(100000, $buckets['current']);
        $this->assertSame(100000, $buckets['days_30']);
        $this->assertSame(100000, $buckets['days_60']);
        $this->assertSame(100000, $buckets['days_90_plus']);
    }

    public function test_bucket_sum_always_equals_the_true_outstanding_balance(): void
    {
        $this->charge(800000, 100);
        $this->charge(400000, 40);
        $this->payment(500000, 20);
        $this->charge(150000, 5);

        $buckets = (new ComputeDebtAging)($this->customer);

        // 800000 + 400000 - 500000 + 150000 = 850000
        $this->assertSame(850000, array_sum($buckets));
    }

    public function test_customer_with_no_ledger_history_has_all_zero_buckets(): void
    {
        $buckets = (new ComputeDebtAging)($this->customer);

        $this->assertSame(0, array_sum($buckets));
    }

    public function test_overpayment_does_not_produce_negative_bucket_amounts(): void
    {
        $this->charge(200000, 10);
        $this->payment(500000, 5); // customer overpaid — balance goes negative, no bucket should go negative

        $buckets = (new ComputeDebtAging)($this->customer);

        $this->assertSame(0, array_sum($buckets));
        foreach ($buckets as $amount) {
            $this->assertGreaterThanOrEqual(0, $amount);
        }
    }
}
