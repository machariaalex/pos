<div>
    <div class="mb-6">
        <a href="{{ route('reports.index') }}" class="inline-flex items-center gap-1 text-sm text-text-muted hover:text-text-secondary">
            <x-heroicon-o-arrow-left class="h-3.5 w-3.5" />
            Reports
        </a>
        <h1 class="mt-1 text-2xl font-semibold text-text-primary">Expenses report</h1>
    </div>

    <div class="mb-6 flex flex-wrap items-end gap-4">
        <div>
            <label class="mb-1 block text-sm font-medium text-text-primary">Quick range</label>
            <div class="flex rounded-lg border border-surface-border p-0.5 text-xs font-medium">
                <button
                    wire:click="setToday"
                    @class(['rounded-md px-2.5 py-1.5 transition-colors', 'bg-primary-700 text-white' => $activePreset === 'today', 'text-text-secondary hover:bg-surface-muted' => $activePreset !== 'today'])
                >
                    Today
                </button>
                <button
                    wire:click="setThisWeek"
                    @class(['rounded-md px-2.5 py-1.5 transition-colors', 'bg-primary-700 text-white' => $activePreset === 'week', 'text-text-secondary hover:bg-surface-muted' => $activePreset !== 'week'])
                >
                    This week
                </button>
                <button
                    wire:click="setThisMonth"
                    @class(['rounded-md px-2.5 py-1.5 transition-colors', 'bg-primary-700 text-white' => $activePreset === 'month', 'text-text-secondary hover:bg-surface-muted' => $activePreset !== 'month'])
                >
                    This month
                </button>
            </div>
        </div>
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
                <option value="none">None (list only)</option>
                <option value="category">Category</option>
            </select>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-stat-card
            icon="arrow-trending-down"
            variant="warn"
            label="Total expenses"
            :value="'KES '.number_format($totalCents / 100, 2)"
        />
    </div>

    @if ($groupBy === 'category')
        <x-table>
            <thead>
                <tr>
                    <x-table.th>Category</x-table.th>
                    <x-table.th align="right">Entries</x-table.th>
                    <x-table.th align="right">Total</x-table.th>
                </tr>
            </thead>
            <tbody>
                @forelse ($byCategory as $row)
                    <tr class="hover:bg-surface-muted/50">
                        <x-table.td>{{ $row->name }}</x-table.td>
                        <x-table.td align="right">
                            <span class="font-tabular text-text-secondary">{{ $row->expense_count }}</span>
                        </x-table.td>
                        <x-table.td align="right">
                            <span class="font-tabular font-semibold text-text-primary">{{ number_format($row->total_cents / 100, 2) }}</span>
                        </x-table.td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">
                            <x-empty-state icon="chart-bar" title="No expenses in this range" description="Try adjusting the date range." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-table>
    @else
        <x-table>
            <thead>
                <tr>
                    <x-table.th>Date</x-table.th>
                    <x-table.th>Category</x-table.th>
                    <x-table.th>Description</x-table.th>
                    <x-table.th>Recorded by</x-table.th>
                    <x-table.th align="right">Amount</x-table.th>
                </tr>
            </thead>
            <tbody>
                @forelse ($entries as $expense)
                    <tr class="hover:bg-surface-muted/50">
                        <x-table.td>
                            <span class="font-tabular text-text-secondary">{{ $expense->incurred_on->toDateString() }}</span>
                        </x-table.td>
                        <x-table.td>{{ $expense->category->name }}</x-table.td>
                        <x-table.td class="text-text-secondary">{{ $expense->description ?: '—' }}</x-table.td>
                        <x-table.td class="text-text-secondary">{{ $expense->createdBy->name }}</x-table.td>
                        <x-table.td align="right">
                            <span class="font-tabular font-semibold text-text-primary">{{ number_format($expense->amount_cents / 100, 2) }}</span>
                        </x-table.td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <x-empty-state icon="chart-bar" title="No expenses in this range" description="Try adjusting the date range." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-table>
    @endif
</div>
