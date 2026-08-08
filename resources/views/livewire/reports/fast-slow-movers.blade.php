<div>
    <div class="mb-6">
        <a href="{{ route('reports.index') }}" class="inline-flex items-center gap-1 text-sm text-text-muted hover:text-text-secondary">
            <x-heroicon-o-arrow-left class="h-3.5 w-3.5" />
            Reports
        </a>
        <h1 class="mt-1 text-2xl font-semibold text-text-primary">Fast &amp; slow movers</h1>
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
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div>
            <h2 class="mb-3 flex items-center gap-2 text-base font-semibold text-text-primary">
                <x-heroicon-o-arrow-trending-up class="h-4 w-4 text-success-600" />
                Fast movers
            </h2>
            <x-table>
                <thead>
                    <tr>
                        <x-table.th>Product</x-table.th>
                        <x-table.th align="right">Sold</x-table.th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($fastMovers as $row)
                        <tr class="hover:bg-surface-muted/50">
                            <x-table.td>{{ $row->name }}</x-table.td>
                            <x-table.td align="right">
                                <span class="font-tabular text-text-secondary">{{ $row->total_quantity }} {{ $row->selling_unit ?? $row->base_unit }}</span>
                            </x-table.td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        </div>

        <div>
            <h2 class="mb-3 flex items-center gap-2 text-base font-semibold text-text-primary">
                <x-heroicon-o-arrow-trending-down class="h-4 w-4 text-danger-600" />
                Slow movers
            </h2>
            <x-table>
                <thead>
                    <tr>
                        <x-table.th>Product</x-table.th>
                        <x-table.th align="right">Sold</x-table.th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($slowMovers as $row)
                        <tr class="hover:bg-surface-muted/50">
                            <x-table.td>{{ $row->name }}</x-table.td>
                            <x-table.td align="right">
                                <span class="font-tabular {{ $row->total_quantity == 0 ? 'font-semibold text-danger-600' : 'text-text-secondary' }}">
                                    {{ $row->total_quantity }} {{ $row->selling_unit ?? $row->base_unit }}
                                </span>
                            </x-table.td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        </div>
    </div>
</div>
