<div>
    <div class="mb-6">
        <a href="{{ route('inventory.products.index') }}" class="inline-flex items-center gap-1 text-sm text-text-muted hover:text-text-primary hover:underline">
            <x-heroicon-o-arrow-left class="h-4 w-4" /> Products
        </a>
        <div class="mt-1 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-text-primary">{{ $product->name }}</h1>
                <p class="text-sm text-text-muted">
                    {{ $product->category->name }} &middot; sold per {{ $product->base_unit }}
                    @if ($product->barcode) &middot; <span class="font-tabular">{{ $product->barcode }}</span> @endif
                    @if ($product->hasBulkPack())
                        &middot; <span class="font-tabular text-success-700">Bulk: {{ number_format($product->pack_size, 0) }} {{ $product->effectiveSellingUnit() }} @ KES {{ number_format($product->pack_price_cents / 100, 2) }}</span>
                    @endif
                </p>
            </div>
            @can('adjust-stock')
                <x-button href="{{ route('inventory.receive-stock', ['product' => $product->id]) }}" variant="primary">
                    <x-heroicon-o-plus class="h-4 w-4" />
                    Receive stock
                </x-button>
            @endcan
        </div>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <x-card :padding="false" class="p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-text-muted">Stock on hand</p>
            <p class="font-tabular mt-1 text-lg font-semibold {{ $product->isLowStock() ? 'text-danger-600' : 'text-text-primary' }}">
                {{ $product->stockOnHand() }} {{ $product->base_unit }}
            </p>
        </x-card>
        <x-card :padding="false" class="p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-text-muted">Reorder level</p>
            <p class="font-tabular mt-1 text-lg font-semibold text-text-primary">{{ $product->reorder_level }} {{ $product->base_unit }}</p>
        </x-card>
        <x-card :padding="false" class="p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-text-muted">Selling price</p>
            <p class="font-tabular mt-1 text-lg font-semibold text-text-primary">KES {{ number_format($product->selling_price_cents / 100, 2) }}</p>
        </x-card>
        @can('view-buying-price')
            <x-card :padding="false" class="p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-text-muted">Buying price</p>
                <p class="font-tabular mt-1 text-lg font-semibold text-text-primary">KES {{ number_format($product->buying_price_cents / 100, 2) }}</p>
            </x-card>
        @endcan
    </div>

    <h2 class="mb-3 text-lg font-semibold text-text-primary">Batches</h2>
    <div class="mb-8">
        <x-table>
            <thead class="border-b border-surface-border bg-surface-muted text-left text-xs font-semibold uppercase tracking-wide text-text-muted">
                <tr>
                    <th class="px-4 py-3">Batch</th>
                    <th class="px-4 py-3">Expiry</th>
                    <th class="px-4 py-3">Remaining</th>
                    @can('view-buying-price')
                        <th class="px-4 py-3">Cost/{{ $product->base_unit }}</th>
                    @endcan
                    <th class="px-4 py-3">Received</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-border">
                @forelse ($batches as $batch)
                    <tr class="hover:bg-surface-muted {{ $batch->quantity_remaining == 0 ? 'opacity-50' : '' }}">
                        <td class="px-4 py-3 font-medium text-text-primary">{{ $batch->batch_number }}</td>
                        <td class="px-4 py-3">
                            @if (!$batch->expiry_date)
                                <span class="text-text-muted">&mdash;</span>
                            @elseif ($batch->isExpired())
                                <x-badge variant="danger">Expired {{ $batch->expiry_date->toDateString() }}</x-badge>
                            @elseif ($batch->isExpiringSoon())
                                <x-badge variant="warn">Expires {{ $batch->expiry_date->toDateString() }}</x-badge>
                            @else
                                <span class="text-text-secondary">{{ $batch->expiry_date->toDateString() }}</span>
                            @endif
                        </td>
                        <td class="font-tabular px-4 py-3 text-text-secondary">{{ $batch->quantity_remaining }} {{ $product->base_unit }}</td>
                        @can('view-buying-price')
                            <td class="font-tabular px-4 py-3 text-text-secondary">KES {{ number_format($batch->buying_price_cents / 100, 2) }}</td>
                        @endcan
                        <td class="px-4 py-3 text-text-muted">{{ $batch->received_at->toDateString() }}</td>
                        <td class="px-4 py-3 text-right">
                            @can('adjust-stock')
                                <button wire:click="startAdjust({{ $batch->id }})" class="text-sm font-medium text-primary-700 hover:underline">Adjust</button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <x-empty-state icon="archive-box" title="No batches yet" description="Add a batch to start tracking stock for this product." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-table>
    </div>

    @can('adjust-stock')
        <h2 class="mb-3 text-lg font-semibold text-text-primary">Recent stock adjustments</h2>
        <x-table>
            <thead class="border-b border-surface-border bg-surface-muted text-left text-xs font-semibold uppercase tracking-wide text-text-muted">
                <tr>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Batch</th>
                    <th class="px-4 py-3">Change</th>
                    <th class="px-4 py-3">Reason</th>
                    <th class="px-4 py-3">By</th>
                    <th class="px-4 py-3">Notes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-border">
                @forelse ($recentAdjustments as $adjustment)
                    <tr class="hover:bg-surface-muted">
                        <td class="px-4 py-3 text-text-muted">{{ $adjustment->created_at->format('d M Y H:i') }}</td>
                        <td class="px-4 py-3 text-text-secondary">{{ $adjustment->batch->batch_number }}</td>
                        <td class="font-tabular px-4 py-3 {{ $adjustment->quantity_delta >= 0 ? 'text-success-700' : 'text-danger-600' }}">
                            {{ $adjustment->quantity_delta >= 0 ? '+' : '' }}{{ $adjustment->quantity_delta }}
                        </td>
                        <td class="px-4 py-3 text-text-secondary">{{ str($adjustment->reason)->headline() }}</td>
                        <td class="px-4 py-3 text-text-secondary">{{ $adjustment->user->name }}</td>
                        <td class="px-4 py-3 text-text-muted">{{ $adjustment->notes }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <x-empty-state icon="clipboard-document-list" title="No adjustments recorded" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-table>
    @endcan

    {{-- Adjust stock modal --}}
    @if ($adjustingBatchId)
        <x-modal title="Adjust stock" wire:click.self="$set('adjustingBatchId', null)">
            <form wire:submit="adjustStock" class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-text-secondary">
                        Quantity change ({{ $product->base_unit }}) &mdash; negative to remove, positive to add
                    </label>
                    <input type="number" step="0.001" wire:model="quantityDelta" placeholder="e.g. -2.5" class="font-tabular w-full rounded-lg border border-surface-border px-3 py-2.5 text-sm focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600">
                    @error('quantityDelta') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-text-secondary">Reason</label>
                    <select wire:model="reason" class="w-full rounded-lg border border-surface-border px-3 py-2.5 text-sm focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600">
                        <option value="">Select a reason</option>
                        @foreach ($reasons as $r)
                            <option value="{{ $r }}">{{ str($r)->headline() }}</option>
                        @endforeach
                    </select>
                    @error('reason') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-text-secondary">Notes</label>
                    <textarea wire:model="notes" rows="2" class="w-full rounded-lg border border-surface-border px-3 py-2.5 text-sm focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600"></textarea>
                    @error('notes') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <x-button type="button" wire:click="$set('adjustingBatchId', null)" variant="ghost">
                        Cancel
                    </x-button>
                    <x-button type="submit" variant="primary">
                        Apply adjustment
                    </x-button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
