<div>
    <h1 class="mb-6 text-2xl font-semibold text-text-primary">Cash-up</h1>

    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card title="Today's drawer">
            <p class="mb-4 text-sm text-text-muted">
                Expected cash from your sales today:
                <span class="font-tabular font-semibold text-text-primary">KES {{ number_format($expectedToday / 100, 2) }}</span>
            </p>

            <form wire:submit="declare" class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-text-primary">Cash counted in drawer (KES)</label>
                    <input type="number" step="0.01" wire:model="declaredAmount" class="w-full rounded-lg border border-surface-border bg-surface-card px-3 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary-700/30">
                    @error('declaredAmount') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-text-primary">Notes (optional)</label>
                    <input type="text" wire:model="notes" class="w-full rounded-lg border border-surface-border bg-surface-card px-3 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary-700/30">
                </div>
                <x-button variant="primary" type="submit">
                    {{ $todaysCashUp ? 'Update declaration' : 'Declare' }}
                </x-button>
            </form>

            @if ($todaysCashUp)
                <div class="mt-5 space-y-2 rounded-lg border border-surface-border bg-surface-muted p-4 text-sm">
                    <div class="flex justify-between">
                        <span class="text-text-muted">Expected</span>
                        <span class="font-tabular font-medium text-text-primary">KES {{ number_format($todaysCashUp->expected_cash_cents / 100, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-muted">Declared</span>
                        <span class="font-tabular font-medium text-text-primary">KES {{ number_format($todaysCashUp->declared_cash_cents / 100, 2) }}</span>
                    </div>
                    <div class="flex justify-between border-t border-surface-border pt-2">
                        <span class="font-medium text-text-primary">Variance</span>
                        <span class="font-tabular font-semibold {{ $todaysCashUp->variance_cents == 0 ? 'text-success-600' : 'text-danger-600' }}">
                            KES {{ number_format($todaysCashUp->variance_cents / 100, 2) }}
                        </span>
                    </div>
                </div>
            @endif
        </x-card>
    </div>

    <h2 class="mb-3 text-base font-semibold text-text-primary">History</h2>
    <x-table>
        <thead>
            <tr>
                <x-table.th>Date</x-table.th>
                @can('view-reports')
                    <x-table.th>Attendant</x-table.th>
                @endcan
                <x-table.th align="right">Expected</x-table.th>
                <x-table.th align="right">Declared</x-table.th>
                <x-table.th align="right">Variance</x-table.th>
            </tr>
        </thead>
        <tbody>
            @forelse ($history as $cashUp)
                <tr class="hover:bg-surface-muted/50">
                    <x-table.td>
                        <span class="text-text-secondary">{{ $cashUp->business_date->toDateString() }}</span>
                    </x-table.td>
                    @can('view-reports')
                        <x-table.td>
                            <span class="text-text-secondary">{{ $cashUp->user->name }}</span>
                        </x-table.td>
                    @endcan
                    <x-table.td align="right">
                        <span class="font-tabular text-text-secondary">{{ number_format($cashUp->expected_cash_cents / 100, 2) }}</span>
                    </x-table.td>
                    <x-table.td align="right">
                        <span class="font-tabular text-text-secondary">{{ number_format($cashUp->declared_cash_cents / 100, 2) }}</span>
                    </x-table.td>
                    <x-table.td align="right">
                        <span class="font-tabular font-semibold {{ $cashUp->variance_cents == 0 ? 'text-success-600' : 'text-danger-600' }}">
                            {{ number_format($cashUp->variance_cents / 100, 2) }}
                        </span>
                    </x-table.td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        <x-empty-state icon="banknotes" title="No cash-ups declared yet" />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>
</div>
