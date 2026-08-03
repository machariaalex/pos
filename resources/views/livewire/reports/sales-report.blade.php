<div>
    <div class="mb-6">
        <a href="{{ route('reports.index') }}" class="inline-flex items-center gap-1 text-sm text-text-muted hover:text-text-secondary">
            <x-heroicon-o-arrow-left class="h-3.5 w-3.5" />
            Reports
        </a>
        <h1 class="mt-1 text-2xl font-semibold text-text-primary">Sales report</h1>
    </div>

    <div class="mb-6 flex flex-wrap items-end gap-4">
        <div>
            <label class="mb-1 block text-sm font-medium text-text-primary">From</label>
            <input type="date" wire:model.live="dateFrom" class="rounded-lg border border-surface-border bg-surface-card px-3 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary-700/30">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-text-primary">To</label>
            <input type="date" wire:model.live="dateTo" class="rounded-lg border border-surface-border bg-surface-card px-3 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary-700/30">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-text-primary">Group by</label>
            <select wire:model.live="groupBy" class="rounded-lg border border-surface-border bg-surface-card px-3 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary-700/30">
                <option value="none">None (totals only)</option>
                <option value="product">Product</option>
                <option value="category">Category</option>
                <option value="attendant">Attendant</option>
            </select>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4">
        <x-stat-card
            icon="shopping-bag"
            variant="primary"
            label="Total sales"
            :value="'KES '.number_format($totals['total_cents'] / 100, 2)"
        />
        <x-stat-card
            icon="receipt-percent"
            variant="info"
            label="Transactions"
            :value="$totals['transaction_count']"
        />
    </div>

    @if ($groupBy !== 'none')
        <x-table>
            <thead>
                <tr>
                    @if ($groupBy === 'product')
                        <x-table.th>Product</x-table.th>
                        <x-table.th align="right">Quantity sold</x-table.th>
                        <x-table.th align="right">Revenue</x-table.th>
                    @elseif ($groupBy === 'category')
                        <x-table.th>Category</x-table.th>
                        <x-table.th align="right">Revenue</x-table.th>
                    @elseif ($groupBy === 'attendant')
                        <x-table.th>Attendant</x-table.th>
                        <x-table.th align="right">Transactions</x-table.th>
                        <x-table.th align="right">Revenue</x-table.th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($breakdown as $row)
                    <tr class="hover:bg-surface-muted/50">
                        @if ($groupBy === 'product')
                            <x-table.td>{{ $row->name }}</x-table.td>
                            <x-table.td align="right">
                                <span class="font-tabular text-text-secondary">{{ $row->total_quantity }} {{ $row->base_unit }}</span>
                            </x-table.td>
                            <x-table.td align="right">
                                <span class="font-tabular font-semibold text-text-primary">{{ number_format($row->total_cents / 100, 2) }}</span>
                            </x-table.td>
                        @elseif ($groupBy === 'category')
                            <x-table.td>{{ $row->name }}</x-table.td>
                            <x-table.td align="right">
                                <span class="font-tabular font-semibold text-text-primary">{{ number_format($row->total_cents / 100, 2) }}</span>
                            </x-table.td>
                        @elseif ($groupBy === 'attendant')
                            <x-table.td>{{ $row->name }}</x-table.td>
                            <x-table.td align="right">
                                <span class="font-tabular text-text-secondary">{{ $row->transaction_count }}</span>
                            </x-table.td>
                            <x-table.td align="right">
                                <span class="font-tabular font-semibold text-text-primary">{{ number_format($row->total_cents / 100, 2) }}</span>
                            </x-table.td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">
                            <x-empty-state icon="chart-bar" title="No sales in this range" description="Try adjusting the date range." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-table>
    @endif
</div>
