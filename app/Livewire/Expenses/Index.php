<?php

namespace App\Livewire\Expenses;

use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    public ?int $expenseCategoryId = null;

    public string $amount = '';

    public string $incurredOn = '';

    public string $description = '';

    public bool $showCategoryForm = false;

    public string $newCategoryName = '';

    public function mount(): void
    {
        Gate::authorize('manage-expenses');

        $this->incurredOn = now()->toDateString();
    }

    public function addCategory(): void
    {
        Gate::authorize('manage-expenses');

        $data = $this->validate([
            'newCategoryName' => ['required', 'string', 'max:255', Rule::unique('expense_categories', 'name')],
        ]);

        $category = ExpenseCategory::create(['name' => $data['newCategoryName']]);
        AuditLog::record('expense_category.created', $category, "Created expense category {$category->name}");

        $this->expenseCategoryId = $category->id;
        $this->newCategoryName = '';
        $this->showCategoryForm = false;
    }

    public function save(): void
    {
        Gate::authorize('manage-expenses');

        $data = $this->validate([
            'expenseCategoryId' => ['required', 'exists:expense_categories,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'incurredOn' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $expense = Expense::create([
            'expense_category_id' => $data['expenseCategoryId'],
            'amount_cents' => (int) round($data['amount'] * 100),
            'description' => $data['description'] ?: null,
            'incurred_on' => $data['incurredOn'],
            'created_by' => auth()->id(),
        ]);

        AuditLog::record(
            'expense.created',
            $expense,
            auth()->user()->name." recorded a KES ".number_format($expense->amount_cents / 100, 2)." expense ({$expense->category->name})",
        );

        $this->reset(['expenseCategoryId', 'amount', 'description']);
        $this->incurredOn = now()->toDateString();
    }

    public function render()
    {
        return view('livewire.expenses.index', [
            'categories' => ExpenseCategory::orderBy('name')->get(),
            'recentExpenses' => Expense::with(['category', 'createdBy'])
                ->latest('incurred_on')
                ->latest('id')
                ->limit(15)
                ->get(),
        ]);
    }
}
