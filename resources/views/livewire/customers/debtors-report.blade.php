<div>
    <div class="mb-6 flex items-center justify-between print:hidden">
        <h1 class="text-2xl font-semibold text-text-primary">Debtors report</h1>
        <x-button variant="secondary" onclick="window.print()">
            <x-heroicon-o-printer class="h-4 w-4" />
            Print
        </x-button>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-5">
        <x-stat-card
            icon="banknotes"
            variant="primary"
            label="Total outstanding"
            :value="'KES '.number_format($grandTotal / 100, 2)"
        />
        <x-stat-card
            icon="clock"
            variant="success"
            label="Current"
            :value="'KES '.number_format($totals['current'] / 100, 2)"
        />
        <x-stat-card
            icon="clock"
            variant="warn"
            label="30 days"
            :value="'KES '.number_format($totals['days_30'] / 100, 2)"
        />
        <x-stat-card
            icon="exclamation-triangle"
            variant="warn"
            label="60 days"
            :value="'KES '.number_format($totals['days_60'] / 100, 2)"
        />
        <x-stat-card
            icon="exclamation-circle"
            variant="danger"
            label="90+ days"
            :value="'KES '.number_format($totals['days_90_plus'] / 100, 2)"
        />
    </div>

    <x-table>
        <thead>
            <tr>
                <x-table.th>Customer</x-table.th>
                <x-table.th>Phone</x-table.th>
                <x-table.th align="right">Current</x-table.th>
                <x-table.th align="right">30 days</x-table.th>
                <x-table.th align="right">60 days</x-table.th>
                <x-table.th align="right">90+ days</x-table.th>
                <x-table.th align="right">Total</x-table.th>
            </tr>
        </thead>
        <tbody>
            @forelse ($debtors as $row)
                <tr class="hover:bg-surface-muted/50">
                    <x-table.td>
                        <a href="{{ route('customers.show', $row['customer']) }}" class="font-medium text-primary-700 hover:underline">
                            {{ $row['customer']->name }}
                        </a>
                    </x-table.td>
                    <x-table.td>
                        <span class="text-text-muted">{{ $row['customer']->phone }}</span>
                    </x-table.td>
                    <x-table.td align="right">
                        <span class="font-tabular text-text-secondary">{{ number_format($row['aging']['current'] / 100, 2) }}</span>
                    </x-table.td>
                    <x-table.td align="right">
                        <span class="font-tabular text-warn-600">{{ number_format($row['aging']['days_30'] / 100, 2) }}</span>
                    </x-table.td>
                    <x-table.td align="right">
                        <span class="font-tabular text-orange-600">{{ number_format($row['aging']['days_60'] / 100, 2) }}</span>
                    </x-table.td>
                    <x-table.td align="right">
                        <span class="font-tabular {{ $row['aging']['days_90_plus'] > 0 ? 'font-semibold text-danger-600' : 'text-text-muted' }}">
                            {{ number_format($row['aging']['days_90_plus'] / 100, 2) }}
                        </span>
                    </x-table.td>
                    <x-table.td align="right">
                        <span class="font-tabular font-semibold text-text-primary">{{ number_format($row['customer']->balance_cents / 100, 2) }}</span>
                    </x-table.td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <x-empty-state icon="check-circle" title="No outstanding debtors" description="All customer balances are clear." />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>
</div>
