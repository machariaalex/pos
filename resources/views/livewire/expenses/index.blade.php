<div class="flex flex-col gap-6 lg:flex-row">
    <div class="min-w-0 flex-1">
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-text-primary">Expenses</h1>
            <a href="{{ route('expenses.categories.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-primary-700 hover:underline">
                <x-heroicon-o-tag class="h-4 w-4" />
                Categories
            </a>
        </div>

        <x-card>
            <form wire:submit="save" class="space-y-4">
                <div>
                    <div class="mb-1 flex items-center justify-between">
                        <label class="block text-sm font-medium text-text-secondary">Category</label>
                        <button type="button" wire:click="$toggle('showCategoryForm')" class="text-xs font-medium text-primary-700 hover:underline">
                            {{ $showCategoryForm ? 'Cancel' : '+ New category' }}
                        </button>
                    </div>

                    @if ($showCategoryForm)
                        <div class="mb-2 flex items-center gap-2">
                            <input
                                type="text"
                                wire:model="newCategoryName"
                                wire:keydown.enter.prevent="addCategory"
                                placeholder="New category name"
                                class="w-full rounded-lg border border-surface-border px-3 py-2.5 text-sm focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600"
                            >
                            <x-button type="button" wire:click="addCategory" variant="secondary" size="md">Add</x-button>
                        </div>
                        @error('newCategoryName') <p class="mb-2 text-sm text-danger-600">{{ $message }}</p> @enderror
                    @endif

                    <select wire:model="expenseCategoryId" class="w-full rounded-lg border border-surface-border px-3 py-2.5 text-sm focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600">
                        <option value="">Select a category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('expenseCategoryId') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-text-secondary">Amount (KES)</label>
                    <input type="number" step="0.01" wire:model="amount" class="font-tabular w-full rounded-lg border border-surface-border px-3 py-2.5 text-sm focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600">
                    @error('amount') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-text-secondary">Date incurred</label>
                    <input type="date" wire:model="incurredOn" class="w-full rounded-lg border border-surface-border px-3 py-2.5 text-sm focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600">
                    @error('incurredOn') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-text-secondary">Description (optional)</label>
                    <input type="text" wire:model="description" placeholder="e.g. August rent" class="w-full rounded-lg border border-surface-border px-3 py-2.5 text-sm focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600">
                    @error('description') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end pt-2">
                    <x-button type="submit" variant="primary">Record expense</x-button>
                </div>
            </form>
        </x-card>
    </div>

    <div class="w-full shrink-0 lg:w-96">
        <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-text-muted">Recent expenses</h2>
        <x-card :padding="false">
            @forelse ($recentExpenses as $expense)
                <div class="flex items-center justify-between gap-3 border-b border-surface-border px-4 py-3 text-sm last:border-0">
                    <div class="min-w-0">
                        <p class="truncate font-medium text-text-primary">{{ $expense->category->name }}</p>
                        <p class="text-xs text-text-muted">
                            {{ $expense->incurred_on->toDateString() }} &middot; {{ $expense->createdBy->name }}
                            @if ($expense->description) &middot; {{ $expense->description }} @endif
                        </p>
                    </div>
                    <p class="font-tabular shrink-0 text-danger-600">-{{ number_format($expense->amount_cents / 100, 2) }}</p>
                </div>
            @empty
                <x-empty-state icon="banknotes" title="No expenses recorded yet" class="py-8" />
            @endforelse
        </x-card>
    </div>
</div>
