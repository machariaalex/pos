<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold text-text-primary">Products</h1>
        @can('edit-price')
            <div class="flex items-center gap-3">
                <x-button href="{{ route('inventory.categories.index') }}" variant="secondary">
                    <x-heroicon-o-tag class="h-4 w-4" />
                    Categories
                </x-button>
                <x-button wire:click="startCreate" variant="primary">
                    <x-heroicon-o-plus class="h-4 w-4" />
                    New product
                </x-button>
            </div>
        @endcan
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-text-muted">
                <x-heroicon-o-magnifying-glass class="h-4 w-4" />
            </div>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search by name or barcode..."
                class="w-64 rounded-lg border border-surface-border bg-surface-card py-2 pl-9 pr-3 text-sm focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600"
            >
        </div>
        <select wire:model.live="categoryFilter" class="rounded-lg border border-surface-border bg-surface-card px-3 py-2 text-sm focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600">
            <option value="">All categories</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </select>
        <label class="flex items-center gap-2 text-sm text-text-secondary">
            <input type="checkbox" wire:model.live="lowStockOnly" class="rounded border-surface-border text-primary-700 focus:ring-primary-600">
            Low stock only
        </label>
    </div>

    <x-table scrollable>
        <thead class="sticky top-0 z-10 border-b border-surface-border bg-surface-muted text-left text-xs font-semibold uppercase tracking-wide text-text-muted">
            <tr>
                <th class="px-4 py-3">Name</th>
                <th class="px-4 py-3">Category</th>
                <th class="px-4 py-3">Unit</th>
                <th class="px-4 py-3">Stock</th>
                @can('view-buying-price')
                    <th class="px-4 py-3">Buying</th>
                @endcan
                <th class="px-4 py-3">Selling</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-surface-border">
            @forelse ($products as $product)
                <tr class="hover:bg-surface-muted">
                    <td class="px-4 py-3">
                        <a href="{{ route('inventory.products.show', $product) }}" class="font-medium text-primary-700 hover:underline">
                            {{ $product->name }}
                        </a>
                        @if ($product->barcode)
                            <div class="font-tabular text-xs text-text-muted">{{ $product->barcode }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-text-secondary">{{ $product->category->name }}</td>
                    <td class="px-4 py-3 text-text-secondary">{{ $product->base_unit }}</td>
                    <td class="px-4 py-3">
                        <span class="font-tabular {{ $product->isLowStock() ? 'font-medium text-danger-600' : 'text-text-primary' }}">
                            {{ $product->stockOnHand() }} {{ $product->base_unit }}
                        </span>
                        @if ($product->isLowStock())
                            <x-badge variant="danger" class="ml-1">Low</x-badge>
                        @endif
                    </td>
                    @can('view-buying-price')
                        <td class="font-tabular px-4 py-3 text-text-secondary">KES {{ number_format($product->buying_price_cents / 100, 2) }}</td>
                    @endcan
                    <td class="font-tabular px-4 py-3 text-text-secondary">KES {{ number_format($product->selling_price_cents / 100, 2) }}</td>
                    <td class="px-4 py-3 text-right">
                        @can('edit-price')
                            <button wire:click="startEdit({{ $product->id }})" class="text-sm font-medium text-primary-700 hover:underline">Edit</button>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <x-empty-state icon="cube" title="No products found" description="Try a different search term or filter." />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>

    <div class="mt-4">
        {{ $products->links() }}
    </div>

    @if ($showForm)
        <x-modal :title="$editingProductId ? 'Edit product' : 'New product'" wire:click.self="$set('showForm', false)">
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-text-secondary">Name</label>
                    <input type="text" wire:model="name" class="w-full rounded-lg border border-surface-border px-3 py-2.5 text-sm focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600">
                    @error('name') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>
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

                    <select wire:model="categoryId" class="w-full rounded-lg border border-surface-border px-3 py-2.5 text-sm focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600">
                        <option value="">Select a category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('categoryId') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-text-secondary">Barcode (optional)</label>
                    <input type="text" wire:model="barcode" class="w-full rounded-lg border border-surface-border px-3 py-2.5 text-sm focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600">
                    @error('barcode') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-text-secondary">Stock unit (how you receive it)</label>
                    <select wire:model.live="baseUnit" class="w-full rounded-lg border border-surface-border px-3 py-2.5 text-sm focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600">
                        @foreach (\App\Models\Product::UNITS as $unit)
                            <option value="{{ $unit }}">{{ $unit }}</option>
                        @endforeach
                    </select>
                </div>
                <label class="flex items-center gap-2 text-sm text-text-secondary">
                    <input type="checkbox" wire:model.live="hasSellingUnit" class="rounded border-surface-border text-primary-700 focus:ring-primary-600">
                    Sell in a different unit (e.g. receive bags, sell per kg)
                </label>
                @if ($hasSellingUnit)
                    <div class="grid grid-cols-2 gap-4 rounded-lg border border-surface-border bg-surface-muted p-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-text-secondary">Selling unit</label>
                            <select wire:model="sellingUnit" class="w-full rounded-lg border border-surface-border bg-surface-card px-3 py-2.5 text-sm focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600">
                                <option value="">Select...</option>
                                @foreach (\App\Models\Product::UNITS as $unit)
                                    <option value="{{ $unit }}">{{ $unit }}</option>
                                @endforeach
                            </select>
                            @error('sellingUnit') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-text-secondary">{{ $sellingUnit ?: 'units' }} per 1 {{ $baseUnit }}</label>
                            <input type="number" step="0.001" min="0.001" wire:model="unitsPerBase" class="font-tabular w-full rounded-lg border border-surface-border bg-surface-card px-3 py-2.5 text-sm focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600" placeholder="e.g. 50">
                            @error('unitsPerBase') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endif

                @php $effectiveUnit = ($hasSellingUnit && $sellingUnit) ? $sellingUnit : $baseUnit; @endphp
                <label class="flex items-center gap-2 text-sm text-text-secondary">
                    <input type="checkbox" wire:model.live="hasBulkPack" class="rounded border-surface-border text-primary-700 focus:ring-primary-600">
                    Offer a discounted bulk pack (e.g. buy 50kg for less per kg)
                </label>
                @if ($hasBulkPack)
                    <div class="grid grid-cols-2 gap-4 rounded-lg border border-surface-border bg-surface-muted p-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-text-secondary">Pack size ({{ $effectiveUnit }})</label>
                            <input type="number" step="0.001" min="0.001" wire:model="packSize" class="font-tabular w-full rounded-lg border border-surface-border bg-surface-card px-3 py-2.5 text-sm focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600" placeholder="e.g. 50">
                            @error('packSize') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-text-secondary">Pack price (KES)</label>
                            <input type="number" step="0.01" min="0.01" wire:model="packPrice" class="font-tabular w-full rounded-lg border border-surface-border bg-surface-card px-3 py-2.5 text-sm focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600" placeholder="e.g. 2500">
                            @error('packPrice') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endif
                <div class="grid grid-cols-2 gap-4">
                    @can('view-buying-price')
                        <div>
                            <label class="mb-1 block text-sm font-medium text-text-secondary">Buying price (KES)</label>
                            <input type="number" step="0.01" wire:model="buyingPrice" class="font-tabular w-full rounded-lg border border-surface-border px-3 py-2.5 text-sm focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600">
                            @error('buyingPrice') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                        </div>
                    @endcan
                    <div>
                        <label class="mb-1 block text-sm font-medium text-text-secondary">Selling price (KES)</label>
                        <input type="number" step="0.01" wire:model="sellingPrice" class="font-tabular w-full rounded-lg border border-surface-border px-3 py-2.5 text-sm focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600">
                        @error('sellingPrice') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-text-secondary">Reorder level ({{ $baseUnit }})</label>
                    <input type="number" step="0.001" wire:model="reorderLevel" class="font-tabular w-full rounded-lg border border-surface-border px-3 py-2.5 text-sm focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600">
                    @error('reorderLevel') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>
                <label class="flex items-center gap-2 text-sm text-text-secondary">
                    <input type="checkbox" wire:model="isActive" class="rounded border-surface-border text-primary-700 focus:ring-primary-600">
                    Active (visible on sales screen)
                </label>

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
