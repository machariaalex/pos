<?php

namespace Tests\Feature\Customers;

use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DebtorsReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendant_cannot_access_debtors_report(): void
    {
        $attendant = User::factory()->attendant()->create();

        $this->actingAs($attendant)->get(route('customers.debtors'))->assertForbidden();
    }

    public function test_manager_can_access_debtors_report(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)->get(route('customers.debtors'))->assertOk();
    }

    public function test_report_only_lists_customers_with_positive_balance(): void
    {
        $manager = User::factory()->manager()->create();
        Customer::create(['name' => 'Owes Money', 'phone' => '0700000001', 'balance_cents' => 500000]);
        Customer::create(['name' => 'Paid Up', 'phone' => '0700000002', 'balance_cents' => 0]);

        $response = $this->actingAs($manager)->get(route('customers.debtors'));

        $response->assertSee('Owes Money');
        $response->assertDontSee('Paid Up');
    }

    public function test_report_shows_correct_aging_bucket_for_an_old_debt(): void
    {
        $manager = User::factory()->manager()->create();
        $customer = Customer::create(['name' => 'Old Debtor', 'phone' => '0700000003', 'balance_cents' => 500000]);

        $entry = new CustomerLedgerEntry([
            'customer_id' => $customer->id,
            'type' => CustomerLedgerEntry::TYPE_CHARGE,
            'amount_cents' => 500000,
            'running_balance_cents' => 500000,
            'created_by' => $manager->id,
        ]);
        $entry->created_at = Carbon::today()->subDays(100);
        $entry->save();

        $response = $this->actingAs($manager)->get(route('customers.debtors'));

        $response->assertSee('5,000.00'); // shows up in the 90+ column
    }
}
