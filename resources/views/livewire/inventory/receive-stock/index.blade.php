<div class="flex flex-col gap-6 lg:flex-row">
    <div class="min-w-0 flex-1">
        <h1 class="mb-4 text-2xl font-semibold text-text-primary">Receive stock</h1>

        @if ($lastReceivedMessage)
            <x-alert-row variant="success" icon="check-circle" class="mb-4">
                {{ $lastReceivedMessage }}
            </x-alert-row>
        @endif

        @if (! $selectedProduct)
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-text-muted">
                    <x-heroicon-o-magnifying-glass class="h-5 w-5" />
                </div>
                <input
                    type="text"
                    wire:model.live.debounce.150ms="search"
                    autofocus
                    placeholder="Search product by name or barcode..."
                    class="w-full rounded-lg border border-surface-border bg-surface-card py-3.5 pl-11 pr-4 text-base focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600"
                    autocomplete="off"
                >

                @if ($search !== '')
                    <div class="absolute inset-x-0 top-full z-10 mt-1 max-h-80 overflow-y-auto rounded-lg border border-surface-border bg-surface-card shadow-lg">
                        @forelse ($searchResults as $product)
                            <button
                                type="button"
                                wire:click="selectProduct({{ $product->id }})"
                                wire:key="search-result-{{ $product->id }}"
                                class="flex w-full items-center justify-between gap-3 border-b border-surface-border px-4 py-3 text-left last:border-0 hover:bg-primary-50"
                            >
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-text-primary">{{ $product->name }}</p>
                                    <p class="text-xs text-text-muted">{{ $product->category->name }} &middot; sold per {{ $product->base_unit }}</p>
                                </div>
                                <p class="font-tabular shrink-0 text-sm text-text-muted">{{ $product->stockOnHand() }} {{ $product->base_unit }} in stock</p>
                            </button>
                        @empty
                            <p class="px-4 py-3 text-sm text-text-muted">No products match.</p>
                        @endforelse
                    </div>
                @endif
            </div>
        @else
            <x-card>
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-text-muted">Receiving stock for</p>
                        <p class="text-lg font-semibold text-text-primary">{{ $selectedProduct->name }}</p>
                        <p class="text-sm text-text-muted">{{ $selectedProduct->category->name }} &middot; sold per {{ $selectedProduct->base_unit }} &middot; {{ $selectedProduct->stockOnHand() }} {{ $selectedProduct->base_unit }} currently in stock</p>
                    </div>
                    <button type="button" wire:click="clearProduct" class="flex h-9 w-9 items-center justify-center rounded-lg text-text-muted hover:bg-surface-muted hover:text-text-primary">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                <form wire:submit="receive" class="space-y-4">
                    {{-- Supplier --}}
                    <div>
                        <div class="mb-1 flex items-center justify-between">
                            <label class="block text-sm font-medium text-text-secondary">Supplier (optional)</label>
                            <button type="button" wire:click="$toggle('showSupplierForm')" class="text-xs font-medium text-primary-700 hover:underline">
                                {{ $showSupplierForm ? 'Cancel' : '+ New supplier' }}
                            </button>
                        </div>
                        @if ($showSupplierForm)
                            <div class="mb-2 space-y-2 rounded-lg border border-surface-border bg-surface-muted p-3">
                                <input type="text" wire:model="newSupplierName" placeholder="Supplier name" class="w-full rounded-lg border border-surface-border bg-surface-card px-3 py-2 text-sm focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600">
                                @error('newSupplierName') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror
                                <input type="text" wire:model="newSupplierPhone" placeholder="Phone (optional)" class="w-full rounded-lg border border-surface-border bg-surface-card px-3 py-2 text-sm focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600">
                                <x-button type="button" wire:click="addSupplier" variant="secondary" size="md">Save supplier</x-button>
                            </div>
                        @endif
                        @if ($selectedSupplier)
                            <div class="flex items-center justify-between rounded-lg bg-surface-muted px-3 py-2 text-sm">
                                <span class="font-medium text-text-primary">{{ $selectedSupplier->name }}
                                    @if ($selectedSupplier->phone) <span class="font-normal text-text-muted">&middot; {{ $selectedSupplier->phone }}</span> @endif
                                </span>
                                <button type="button" wire:click="clearSupplier" class="p-1 text-text-muted hover:text-text-primary">
                                    <x-heroicon-o-x-mark class="h-4 w-4" />
                                </button>
                            </div>
                        @else
                            <input type="text" wire:model.live.debounce.200ms="supplierSearch" placeholder="Search supplier by name..." class="w-full rounded-lg border border-surface-border px-3 py-2.5 text-sm focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600">
                            @if ($supplierSearch !== '')
                                <div class="mt-1 max-h-40 overflow-y-auto rounded-lg border border-surface-border bg-surface-card shadow">
                                    @forelse ($supplierResults as $supplier)
                                        <button type="button" wire:click="selectSupplier({{ $supplier->id }})" class="block w-full border-b border-surface-border px-3 py-2.5 text-left text-sm last:border-0 hover:bg-surface-muted">
                                            {{ $supplier->name }}
                                            @if ($supplier->phone) <span class="text-xs text-text-muted">{{ $supplier->phone }}</span> @endif
                                        </button>
                                    @empty
                                        <p class="px-3 py-2.5 text-sm text-text-muted">No matches. Use "+ New supplier" above.</p>
                                    @endforelse
                                </div>
                            @endif
                        @endif
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-text-secondary">Batch number (optional)</label>
                        <input type="text" wire:model="batchNumber" placeholder="Auto-generated if left blank" class="w-full rounded-lg border border-surface-border px-3 py-2.5 text-sm focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600">
                        @error('batchNumber') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-text-secondary">Expiry date (optional)</label>
                        <input type="date" wire:model="expiryDate" class="w-full rounded-lg border border-surface-border px-3 py-2.5 text-sm focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600">
                        @error('expiryDate') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-text-secondary">Quantity received ({{ $selectedProduct->base_unit }})</label>
                        <input type="number" step="0.001" wire:model="quantityReceived" autofocus class="font-tabular w-full rounded-lg border border-surface-border px-3 py-2.5 text-sm focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600">
                        @error('quantityReceived') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-text-secondary">Buying price per {{ $selectedProduct->base_unit }} (KES)</label>
                        <input type="number" step="0.01" wire:model="buyingPrice" class="font-tabular w-full rounded-lg border border-surface-border px-3 py-2.5 text-sm focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600">
                        @error('buyingPrice') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-text-secondary">Received on</label>
                        <input type="date" wire:model="receivedAt" class="w-full rounded-lg border border-surface-border px-3 py-2.5 text-sm focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600">
                        @error('receivedAt') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <x-button type="button" wire:click="clearProduct" variant="ghost">Cancel</x-button>
                        <x-button type="submit" variant="primary">Receive stock</x-button>
                    </div>
                </form>
            </x-card>
        @endif
    </div>

    <div class="w-full shrink-0 lg:w-96">
        <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-text-muted">Recently received</h2>
        <x-card :padding="false">
            @forelse ($recentReceipts as $batch)
                <div class="flex items-center justify-between gap-3 border-b border-surface-border px-4 py-3 text-sm last:border-0">
                    <div class="min-w-0">
                        <p class="truncate font-medium text-text-primary">{{ $batch->product->name }}</p>
                        <p class="text-xs text-text-muted">
                            {{ $batch->batch_number }} &middot; {{ $batch->received_at->toDateString() }}
                            @if ($batch->supplier) &middot; {{ $batch->supplier->name }} @endif
                        </p>
                    </div>
                    <p class="font-tabular shrink-0 text-text-secondary">+{{ $batch->quantity_received }} {{ $batch->product->base_unit }}</p>
                </div>
            @empty
                <x-empty-state icon="archive-box" title="No stock received yet" class="py-8" />
            @endforelse
        </x-card>
    </div>
</div>
