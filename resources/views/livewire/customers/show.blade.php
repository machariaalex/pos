<div>
    <div class="mb-6 flex items-center justify-between print:hidden">
        <div>
            <a href="{{ route('customers.index') }}" class="inline-flex items-center gap-1 text-sm text-text-muted hover:text-text-secondary">
                <x-heroicon-o-arrow-left class="h-3.5 w-3.5" />
                Customers
            </a>
            <h1 class="mt-1 text-2xl font-semibold text-text-primary">{{ $customer->name }}</h1>
            <p class="text-sm text-text-muted">{{ $customer->phone }}</p>
        </div>
        <div class="flex gap-3">
            <x-button variant="secondary" onclick="window.print()">
                <x-heroicon-o-printer class="h-4 w-4" />
                Print statement
            </x-button>
            <x-button variant="primary" wire:click="startPayment">
                <x-heroicon-o-banknotes class="h-4 w-4" />
                Record payment
            </x-button>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4 print:hidden">
        <x-stat-card
            icon="credit-card"
            :variant="$customer->balance_cents > 0 ? 'danger' : 'success'"
            label="Balance"
            :value="'KES '.number_format($customer->balance_cents / 100, 2)"
        />
        <x-stat-card
            icon="shield-check"
            variant="info"
            label="Credit limit"
            :value="$customer->hasCreditLimit() ? 'KES '.number_format($customer->credit_limit_cents / 100, 2) : 'No limit'"
        />
        <div class="rounded-card border border-surface-border bg-surface-card p-5 shadow-sm sm:col-span-2">
            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-text-muted">Debt aging</p>
            <div class="grid grid-cols-2 gap-x-4 gap-y-1.5 sm:grid-cols-4">
                <div>
                    <p class="text-xs text-text-muted">Current</p>
                    <p class="font-tabular text-sm font-semibold text-text-primary">KES {{ number_format($aging['current'] / 100, 2) }}</p>
                </div>
                <div>
                    <p class="text-xs text-text-muted">30 days</p>
                    <p class="font-tabular text-sm font-semibold text-warn-600">KES {{ number_format($aging['days_30'] / 100, 2) }}</p>
                </div>
                <div>
                    <p class="text-xs text-text-muted">60 days</p>
                    <p class="font-tabular text-sm font-semibold text-orange-600">KES {{ number_format($aging['days_60'] / 100, 2) }}</p>
                </div>
                <div>
                    <p class="text-xs text-text-muted">90+ days</p>
                    <p class="font-tabular text-sm font-semibold {{ $aging['days_90_plus'] > 0 ? 'text-danger-600' : 'text-text-muted' }}">KES {{ number_format($aging['days_90_plus'] / 100, 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Printable statement --}}
    <div id="statement">
        <div class="mb-4 hidden print:block">
            <div class="text-lg font-bold">{{ config('app.name') }} &mdash; Customer Statement</div>
            <div>{{ $customer->name }} &middot; {{ $customer->phone }}</div>
            <div>Printed: {{ now()->format('d M Y H:i') }}</div>
            <div>Balance: KES {{ number_format($customer->balance_cents / 100, 2) }}</div>
        </div>

        <x-table class="print:border-0 print:shadow-none">
            <thead>
                <tr>
                    <x-table.th>Date</x-table.th>
                    <x-table.th>Type</x-table.th>
                    <x-table.th>Reference</x-table.th>
                    <x-table.th align="right">Amount</x-table.th>
                    <x-table.th align="right">Balance</x-table.th>
                </tr>
            </thead>
            <tbody>
                @forelse ($ledgerEntries as $entry)
                    <tr class="hover:bg-surface-muted/50">
                        <x-table.td>
                            <span class="text-text-muted">{{ $entry->created_at->format('d M Y H:i') }}</span>
                        </x-table.td>
                        <x-table.td>
                            <span class="capitalize text-text-primary">{{ $entry->type }}</span>
                        </x-table.td>
                        <x-table.td>
                            <span class="text-text-muted">{{ $entry->sale?->sale_number ?? $entry->notes ?? '—' }}</span>
                        </x-table.td>
                        <x-table.td align="right">
                            <span class="font-tabular font-medium {{ $entry->type === 'charge' ? 'text-danger-600' : 'text-success-600' }}">
                                {{ $entry->type === 'charge' ? '+' : '-' }}KES {{ number_format($entry->amount_cents / 100, 2) }}
                            </span>
                        </x-table.td>
                        <x-table.td align="right">
                            <span class="font-tabular text-text-primary">KES {{ number_format($entry->running_balance_cents / 100, 2) }}</span>
                        </x-table.td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <x-empty-state icon="document-text" title="No ledger history yet" description="Transactions will appear here once credit sales or payments are recorded." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-table>
    </div>

    @if ($showPaymentForm)
        <x-modal title="Record payment from {{ $customer->name }}" wire:click.self="$set('showPaymentForm', false)">
            <form wire:submit="recordPayment" class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-text-primary">Amount (KES)</label>
                    <input type="number" step="0.01" wire:model="amount" class="w-full rounded-lg border border-surface-border bg-surface-card px-3 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary-700/30">
                    @error('amount') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-text-primary">Method</label>
                    <select wire:model.live="method" class="w-full rounded-lg border border-surface-border bg-surface-card px-3 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary-700/30">
                        <option value="cash">Cash</option>
                        <option value="mpesa">M-Pesa</option>
                    </select>
                </div>
                @if ($method === 'mpesa')
                    <div>
                        <label class="mb-1 block text-sm font-medium text-text-primary">M-Pesa transaction code</label>
                        <input type="text" wire:model="mpesaCode" class="w-full rounded-lg border border-surface-border bg-surface-card px-3 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary-700/30">
                        @error('mpesaCode') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>
                @endif
                <div>
                    <label class="mb-1 block text-sm font-medium text-text-primary">Notes (optional)</label>
                    <input type="text" wire:model="notes" class="w-full rounded-lg border border-surface-border bg-surface-card px-3 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary-700/30">
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <x-button variant="ghost" type="button" wire:click="$set('showPaymentForm', false)">Cancel</x-button>
                    <x-button variant="primary" type="submit">Record payment</x-button>
                </div>
            </form>
        </x-modal>
    @endif

    <style>
        @media print {
            body * { visibility: hidden; }
            #statement, #statement * { visibility: visible; }
            #statement { position: absolute; top: 0; left: 0; width: 100%; }
        }
    </style>
</div>
