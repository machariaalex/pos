<div>
    <div class="mb-6">
        <a href="{{ route('reports.index') }}" class="inline-flex items-center gap-1 text-sm text-text-muted hover:text-text-secondary">
            <x-heroicon-o-arrow-left class="h-3.5 w-3.5" />
            Reports
        </a>
        <h1 class="mt-1 text-2xl font-semibold text-text-primary">Expiry report</h1>
    </div>

    <div class="mb-4 flex gap-2">
        <button wire:click="$set('filter', 'all')" class="rounded-lg px-3 py-1.5 text-sm font-medium transition-colors {{ $filter === 'all' ? 'bg-primary-700 text-white' : 'bg-surface-muted text-text-secondary hover:bg-surface-border' }}">
            All
        </button>
        <button wire:click="$set('filter', 'expired')" class="rounded-lg px-3 py-1.5 text-sm font-medium transition-colors {{ $filter === 'expired' ? 'bg-danger-600 text-white' : 'bg-surface-muted text-text-secondary hover:bg-surface-border' }}">
            Expired
        </button>
        <button wire:click="$set('filter', 'expiring_soon')" class="rounded-lg px-3 py-1.5 text-sm font-medium transition-colors {{ $filter === 'expiring_soon' ? 'bg-warn-600 text-white' : 'bg-surface-muted text-text-secondary hover:bg-surface-border' }}">
            Expiring &le;60 days
        </button>
    </div>

    <x-table>
        <thead>
            <tr>
                <x-table.th>Product</x-table.th>
                <x-table.th>Batch</x-table.th>
                <x-table.th>Expiry</x-table.th>
                <x-table.th align="right">Remaining</x-table.th>
            </tr>
        </thead>
        <tbody>
            @forelse ($batches as $batch)
                <tr class="hover:bg-surface-muted/50">
                    <x-table.td>
                        <a href="{{ route('inventory.products.show', $batch->product) }}" class="font-medium text-primary-700 hover:underline">
                            {{ $batch->product->name }}
                        </a>
                    </x-table.td>
                    <x-table.td>
                        <span class="text-text-secondary">{{ $batch->batch_number }}</span>
                    </x-table.td>
                    <x-table.td>
                        @if ($batch->isExpired())
                            <x-badge variant="danger">Expired {{ $batch->expiry_date->toDateString() }}</x-badge>
                        @elseif ($batch->isExpiringSoon())
                            <x-badge variant="warn">{{ $batch->expiry_date->toDateString() }}</x-badge>
                        @else
                            <span class="text-text-secondary">{{ $batch->expiry_date->toDateString() }}</span>
                        @endif
                    </x-table.td>
                    <x-table.td align="right">
                        <span class="font-tabular text-text-primary">{{ $batch->quantity_remaining }} {{ $batch->product->base_unit }}</span>
                    </x-table.td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">
                        <x-empty-state icon="check-circle" title="No batches match this filter" />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>
</div>
