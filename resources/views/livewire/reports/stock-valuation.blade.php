<div>
    <div class="mb-6">
        <a href="{{ route('reports.index') }}" class="inline-flex items-center gap-1 text-sm text-text-muted hover:text-text-secondary">
            <x-heroicon-o-arrow-left class="h-3.5 w-3.5" />
            Reports
        </a>
        <h1 class="mt-1 text-2xl font-semibold text-text-primary">Stock valuation</h1>
        <p class="text-sm text-text-muted">Current stock on hand, valued at buying price.</p>
    </div>

    <div class="mb-6">
        <x-stat-card
            icon="cube"
            variant="info"
            label="Total stock value"
            :value="'KES '.number_format($total / 100, 2)"
        />
    </div>

    <x-table>
        <thead>
            <tr>
                <x-table.th>Category</x-table.th>
                <x-table.th align="right">Value</x-table.th>
            </tr>
        </thead>
        <tbody>
            @forelse ($byCategory as $category => $value)
                <tr class="hover:bg-surface-muted/50">
                    <x-table.td>{{ $category }}</x-table.td>
                    <x-table.td align="right">
                        <span class="font-tabular font-semibold text-text-primary">{{ number_format($value / 100, 2) }}</span>
                    </x-table.td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">
                        <x-empty-state icon="cube" title="No stock on hand" description="Receive stock to see valuation by category." />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>
</div>
