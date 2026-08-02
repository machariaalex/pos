<?php

namespace App\Livewire\Inventory\Categories;

use App\Models\AuditLog;
use App\Models\Category;
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

    public function startCreate(): void
    {
        Gate::authorize('edit-price');

        $this->reset(['editingCategoryId', 'name']);
        $this->showForm = true;
    }

    public function startEdit(int $categoryId): void
    {
        Gate::authorize('edit-price');

        $category = Category::findOrFail($categoryId);
        $this->editingCategoryId = $category->id;
        $this->name = $category->name;
        $this->showForm = true;
    }

    public function save(): void
    {
        Gate::authorize('edit-price');

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($this->editingCategoryId)],
        ]);

        if ($this->editingCategoryId) {
            $category = Category::findOrFail($this->editingCategoryId);
            $before = $category->only('name');
            $category->update($data);

            AuditLog::record('category.updated', $category, "Renamed category to {$category->name}", $before, $data);
        } else {
            $category = Category::create($data);
            AuditLog::record('category.created', $category, "Created category {$category->name}");
        }

        $this->showForm = false;
    }

    public function delete(int $categoryId): void
    {
        Gate::authorize('edit-price');

        $category = Category::withCount('products')->findOrFail($categoryId);

        if ($category->products_count > 0) {
            $this->addError('delete', "Cannot delete \"{$category->name}\" — {$category->products_count} product(s) still use it.");

            return;
        }

        $name = $category->name;
        $category->delete();
        AuditLog::record('category.deleted', null, "Deleted category {$name}");
    }

    public function render()
    {
        return view('livewire.inventory.categories.index', [
            'categories' => Category::withCount('products')->orderBy('name')->get(),
        ]);
    }
}
