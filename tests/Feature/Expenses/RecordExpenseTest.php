<?php

namespace Tests\Feature\Expenses;

use App\Livewire\Expenses\Index;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class RecordExpenseTest extends TestCase
{
    use RefreshDatabase;

    private ExpenseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = ExpenseCategory::create(['name' => 'Rent']);
    }

    public function test_manager_can_record_an_expense(): void
    {
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)
            ->test(Index::class)
            ->set('expenseCategoryId', $this->category->id)
            ->set('amount', '5000.00')
            ->set('incurredOn', Carbon::now()->toDateString())
            ->set('description', 'August rent')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('expenses', [
            'expense_category_id' => $this->category->id,
            'amount_cents' => 500000,
            'description' => 'August rent',
            'created_by' => $manager->id,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'expense.created']);
    }

    public function test_attendant_cannot_access_expenses(): void
    {
        $attendant = User::factory()->attendant()->create();

        $this->actingAs($attendant)->get(route('expenses.index'))->assertForbidden();
    }

    public function test_attendant_granted_manage_expenses_can_record_one(): void
    {
        $attendant = User::factory()->attendant()->create(['permissions' => ['manage-expenses']]);

        $this->actingAs($attendant)->get(route('expenses.index'))->assertOk();

        Livewire::actingAs($attendant)
            ->test(Index::class)
            ->set('expenseCategoryId', $this->category->id)
            ->set('amount', '1500.00')
            ->set('incurredOn', Carbon::now()->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('expenses', [
            'expense_category_id' => $this->category->id,
            'amount_cents' => 150000,
            'created_by' => $attendant->id,
        ]);
    }

    public function test_manager_can_quick_add_a_category_and_it_gets_selected(): void
    {
        $manager = User::factory()->manager()->create();

        $component = Livewire::actingAs($manager)
            ->test(Index::class)
            ->set('newCategoryName', 'Transport')
            ->call('addCategory')
            ->assertHasNoErrors();

        $category = ExpenseCategory::where('name', 'Transport')->firstOrFail();
        $component->assertSet('expenseCategoryId', $category->id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'expense_category.created']);
    }

    public function test_amount_must_be_greater_than_zero(): void
    {
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)
            ->test(Index::class)
            ->set('expenseCategoryId', $this->category->id)
            ->set('amount', '0')
            ->set('incurredOn', Carbon::now()->toDateString())
            ->call('save')
            ->assertHasErrors('amount');

        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_recording_without_a_category_fails_validation(): void
    {
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)
            ->test(Index::class)
            ->set('amount', '100')
            ->set('incurredOn', Carbon::now()->toDateString())
            ->call('save')
            ->assertHasErrors('expenseCategoryId');

        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_form_resets_after_a_successful_save(): void
    {
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)
            ->test(Index::class)
            ->set('expenseCategoryId', $this->category->id)
            ->set('amount', '250')
            ->set('incurredOn', Carbon::now()->toDateString())
            ->set('description', 'Fuel')
            ->call('save')
            ->assertSet('amount', '')
            ->assertSet('description', '')
            ->assertSet('expenseCategoryId', null);
    }

    public function test_recent_expenses_list_shows_logged_entries(): void
    {
        $manager = User::factory()->manager()->create();
        Expense::create([
            'expense_category_id' => $this->category->id,
            'amount_cents' => 300000,
            'incurred_on' => Carbon::now()->toDateString(),
            'created_by' => $manager->id,
        ]);

        Livewire::actingAs($manager)
            ->test(Index::class)
            ->assertSee('Rent')
            ->assertSee('3,000.00');
    }
}
