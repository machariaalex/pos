<?php

namespace Tests\Feature\Reports;

use App\Livewire\Reports\ExpensesReport;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class ExpensesReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendant_cannot_access_expenses_report(): void
    {
        $attendant = User::factory()->attendant()->create();

        $this->actingAs($attendant)->get(route('reports.expenses'))->assertForbidden();
    }

    public function test_manager_can_access_expenses_report(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)->get(route('reports.expenses'))->assertOk();
    }

    public function test_defaults_to_todays_range(): void
    {
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)
            ->test(ExpensesReport::class)
            ->assertSet('dateFrom', Carbon::today()->toDateString())
            ->assertSet('dateTo', Carbon::today()->toDateString())
            ->assertSet('activePreset', 'today');
    }

    public function test_this_week_preset_sets_range_from_start_of_week(): void
    {
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)
            ->test(ExpensesReport::class)
            ->call('setThisWeek')
            ->assertSet('dateFrom', Carbon::today()->startOfWeek()->toDateString())
            ->assertSet('dateTo', Carbon::today()->toDateString())
            ->assertSet('activePreset', 'week');
    }

    public function test_this_month_preset_sets_range_from_start_of_month(): void
    {
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)
            ->test(ExpensesReport::class)
            ->call('setThisMonth')
            ->assertSet('dateFrom', Carbon::today()->startOfMonth()->toDateString())
            ->assertSet('dateTo', Carbon::today()->toDateString())
            ->assertSet('activePreset', 'month');
    }

    public function test_manually_changing_the_date_clears_the_active_preset(): void
    {
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)
            ->test(ExpensesReport::class)
            ->call('setThisWeek')
            ->assertSet('activePreset', 'week')
            ->set('dateFrom', Carbon::today()->subDays(2)->toDateString())
            ->assertSet('activePreset', null);
    }

    public function test_total_sums_expenses_in_the_selected_range(): void
    {
        $manager = User::factory()->manager()->create();
        $category = ExpenseCategory::create(['name' => 'Rent']);
        Expense::create([
            'expense_category_id' => $category->id,
            'amount_cents' => 500000,
            'incurred_on' => Carbon::today(),
            'created_by' => $manager->id,
        ]);
        Expense::create([
            'expense_category_id' => $category->id,
            'amount_cents' => 25000,
            'incurred_on' => Carbon::today()->subDays(10),
            'created_by' => $manager->id,
        ]);

        Livewire::actingAs($manager)
            ->test(ExpensesReport::class)
            ->assertSee('5,000.00')
            ->assertDontSee('250.00');
    }

    public function test_grouping_by_category_shows_totals_per_category(): void
    {
        $manager = User::factory()->manager()->create();
        $rent = ExpenseCategory::create(['name' => 'Rent']);
        $transport = ExpenseCategory::create(['name' => 'Transport']);
        Expense::create([
            'expense_category_id' => $rent->id, 'amount_cents' => 500000,
            'incurred_on' => Carbon::today(), 'created_by' => $manager->id,
        ]);
        Expense::create([
            'expense_category_id' => $transport->id, 'amount_cents' => 150000,
            'incurred_on' => Carbon::today(), 'created_by' => $manager->id,
        ]);

        Livewire::actingAs($manager)
            ->test(ExpensesReport::class)
            ->set('groupBy', 'category')
            ->assertSee('Rent')
            ->assertSee('5,000.00')
            ->assertSee('Transport')
            ->assertSee('1,500.00');
    }

    public function test_default_view_lists_individual_expenses(): void
    {
        $manager = User::factory()->manager()->create();
        $category = ExpenseCategory::create(['name' => 'Rent']);
        Expense::create([
            'expense_category_id' => $category->id,
            'amount_cents' => 500000,
            'description' => 'August rent',
            'incurred_on' => Carbon::today(),
            'created_by' => $manager->id,
        ]);

        Livewire::actingAs($manager)
            ->test(ExpensesReport::class)
            ->assertSee('August rent')
            ->assertSee('Rent');
    }

    public function test_expenses_outside_the_range_are_excluded(): void
    {
        $manager = User::factory()->manager()->create();
        $category = ExpenseCategory::create(['name' => 'Rent']);
        Expense::create([
            'expense_category_id' => $category->id,
            'amount_cents' => 500000,
            'description' => 'Old expense',
            'incurred_on' => Carbon::today()->subDays(5),
            'created_by' => $manager->id,
        ]);

        Livewire::actingAs($manager)
            ->test(ExpensesReport::class)
            ->assertDontSee('Old expense');
    }
}
