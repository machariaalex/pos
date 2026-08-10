<?php

namespace App\Livewire\Expenses\Categories;

use App\Models\AuditLog;
use App\Models\ExpenseCategory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    public bool $showForm = false;

    public ?int $editingCategoryId = null;

    public string $name = '';

    public function mount(): void
    {
        Gate::authorize('manage-expenses');
    }

    public function startCreate(): void
    {
        Gate::authorize('manage-expenses');

        $this->reset(['editingCategoryId', 'name']);
        $this->showForm = true;
    }

    public function startEdit(int $categoryId): void
    {
        Gate::authorize('manage-expenses');

        $category = ExpenseCategory::findOrFail($categoryId);
        $this->editingCategoryId = $category->id;
        $this->name = $category->name;
        $this->showForm = true;
    }

    public function save(): void
    {
        Gate::authorize('manage-expenses');

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('expense_categories', 'name')->ignore($this->editingCategoryId)],
        ]);

        if ($this->editingCategoryId) {
            $category = ExpenseCategory::findOrFail($this->editingCategoryId);
            $before = $category->only('name');
            $category->update($data);

            AuditLog::record('expense_category.updated', $category, "Renamed expense category to {$category->name}", $before, $data);
        } else {
            $category = ExpenseCategory::create($data);
            AuditLog::record('expense_category.created', $category, "Created expense category {$category->name}");
        }

        $this->showForm = false;
    }

    public function delete(int $categoryId): void
    {
        Gate::authorize('manage-expenses');

        $category = ExpenseCategory::withCount('expenses')->findOrFail($categoryId);

        if ($category->expenses_count > 0) {
            $this->addError('delete', "Cannot delete \"{$category->name}\" — {$category->expenses_count} expense(s) still use it.");

            return;
        }

        $name = $category->name;
        $category->delete();
        AuditLog::record('expense_category.deleted', null, "Deleted expense category {$name}");
    }

    public function render()
    {
        return view('livewire.expenses.categories.index', [
            'categories' => ExpenseCategory::withCount('expenses')->orderBy('name')->get(),
        ]);
    }
}
