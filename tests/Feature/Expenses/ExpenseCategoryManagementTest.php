<?php

namespace Tests\Feature\Expenses;

use App\Livewire\Expenses\Categories\Index;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExpenseCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendant_cannot_view_expense_categories(): void
    {
        $attendant = User::factory()->attendant()->create();
        ExpenseCategory::create(['name' => 'Rent']);

        $this->actingAs($attendant)->get(route('expenses.categories.index'))->assertForbidden();
    }

    public function test_manager_can_view_expense_categories(): void
    {
        $manager = User::factory()->manager()->create();
        ExpenseCategory::create(['name' => 'Rent']);

        $this->actingAs($manager)->get(route('expenses.categories.index'))->assertOk();
    }

    public function test_manager_can_create_an_expense_category(): void
    {
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)
            ->test(Index::class)
            ->call('startCreate')
            ->set('name', 'Wages')
            ->call('save');

        $this->assertDatabaseHas('expense_categories', ['name' => 'Wages']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'expense_category.created']);
    }

    public function test_manager_can_rename_an_expense_category(): void
    {
        $manager = User::factory()->manager()->create();
        $category = ExpenseCategory::create(['name' => 'Rent']);

        Livewire::actingAs($manager)
            ->test(Index::class)
            ->call('startEdit', $category->id)
            ->set('name', 'Shop Rent')
            ->call('save');

        $this->assertDatabaseHas('expense_categories', ['id' => $category->id, 'name' => 'Shop Rent']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'expense_category.updated']);
    }

    public function test_creating_an_expense_category_rejects_a_duplicate_name(): void
    {
        $manager = User::factory()->manager()->create();
        ExpenseCategory::create(['name' => 'Rent']);

        Livewire::actingAs($manager)
            ->test(Index::class)
            ->call('startCreate')
            ->set('name', 'Rent')
            ->call('save')
            ->assertHasErrors('name');
    }

    public function test_manager_can_delete_an_unused_expense_category(): void
    {
        $manager = User::factory()->manager()->create();
        $category = ExpenseCategory::create(['name' => 'Unused']);

        Livewire::actingAs($manager)
            ->test(Index::class)
            ->call('delete', $category->id);

        $this->assertDatabaseMissing('expense_categories', ['id' => $category->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'expense_category.deleted']);
    }

    public function test_cannot_delete_an_expense_category_still_used_by_expenses(): void
    {
        $manager = User::factory()->manager()->create();
        $category = ExpenseCategory::create(['name' => 'Rent']);
        Expense::create([
            'expense_category_id' => $category->id,
            'amount_cents' => 500000,
            'incurred_on' => now()->toDateString(),
            'created_by' => $manager->id,
        ]);

        Livewire::actingAs($manager)
            ->test(Index::class)
            ->call('delete', $category->id)
            ->assertHasErrors('delete');

        $this->assertDatabaseHas('expense_categories', ['id' => $category->id]);
    }
}
