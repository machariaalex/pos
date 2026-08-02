<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="{{ route('inventory.products.index') }}" class="inline-flex items-center gap-1 text-sm text-text-muted hover:text-text-primary hover:underline">
                <x-heroicon-o-arrow-left class="h-4 w-4" /> Products
            </a>
            <h1 class="mt-1 text-2xl font-semibold text-text-primary">Categories</h1>
        </div>
        @can('edit-price')
            <x-button wire:click="startCreate" variant="primary">
                <x-heroicon-o-plus class="h-4 w-4" />
                New category
            </x-button>
        @endcan
    </div>

    @error('delete') <p class="mb-4 rounded-lg bg-danger-50 px-3 py-2 text-sm text-danger-700">{{ $message }}</p> @enderror

    <x-table>
        <thead class="border-b border-surface-border bg-surface-muted text-left text-xs font-semibold uppercase tracking-wide text-text-muted">
            <tr>
                <th class="px-4 py-3">Name</th>
                <th class="px-4 py-3">Products</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-surface-border">
            @forelse ($categories as $category)
                <tr class="hover:bg-surface-muted">
                    <td class="px-4 py-3 font-medium text-text-primary">{{ $category->name }}</td>
                    <td class="font-tabular px-4 py-3 text-text-secondary">{{ $category->products_count }}</td>
                    <td class="px-4 py-3 text-right">
                        @can('edit-price')
                            <div class="flex items-center justify-end gap-3">
                                <button wire:click="startEdit({{ $category->id }})" class="text-sm font-medium text-primary-700 hover:underline">Rename</button>
                                @if ($category->products_count === 0)
                                    <button
                                        wire:click="delete({{ $category->id }})"
                                        wire:confirm="Delete the category &quot;{{ $category->name }}&quot;? This cannot be undone."
                                        class="text-sm font-medium text-danger-600 hover:underline"
                                    >
                                        Delete
                                    </button>
                                @else
                                    <span class="text-sm text-text-muted" title="Cannot delete while products use this category">Delete</span>
                                @endif
                            </div>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">
                        <x-empty-state icon="tag" title="No categories yet" description="Create a category to start organizing products." />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>

    @if ($showForm)
        <x-modal :title="$editingCategoryId ? 'Rename category' : 'New category'" wire:click.self="$set('showForm', false)">
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-text-secondary">Name</label>
                    <input type="text" wire:model="name" class="w-full rounded-lg border border-surface-border px-3 py-2.5 text-sm focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600" autofocus>
                    @error('name') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <x-button type="button" wire:click="$set('showForm', false)" variant="ghost">
                        Cancel
                    </x-button>
                    <x-button type="submit" variant="primary">
                        Save
                    </x-button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
