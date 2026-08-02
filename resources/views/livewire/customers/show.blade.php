<div>
    <div class="mb-6 flex items-center justify-between print:hidden">
        <div>
            <a href="{{ route('customers.index') }}" class="text-sm text-slate-500 hover:underline">&larr; Customers</a>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">{{ $customer->name }}</h1>
            <p class="text-sm text-slate-500">{{ $customer->phone }}</p>
        </div>
        <div class="flex gap-3">
            <button onclick="window.print()" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Print statement
            </button>
            <button wire:click="startPayment" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                Record payment
            </button>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4 print:hidden">
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-sm text-slate-500">Balance</p>
            <p class="mt-1 text-lg font-semibold {{ $customer->balance_cents > 0 ? 'text-red-600' : 'text-slate-900' }}">
                KES {{ number_format($customer->balance_cents / 100, 2) }}
            </p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-sm text-slate-500">Credit limit</p>
            <p class="mt-1 text-lg font-semibold text-slate-900">
                {{ $customer->hasCreditLimit() ? 'KES '.number_format($customer->credit_limit_cents / 100, 2) : 'No limit' }}
            </p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 sm:col-span-2">
            <p class="mb-1 text-sm text-slate-500">Aging</p>
            <div class="flex gap-3 text-xs">
                <span>Current: KES {{ number_format($aging['current'] / 100, 2) }}</span>
                <span>30d: KES {{ number_format($aging['days_30'] / 100, 2) }}</span>
                <span>60d: KES {{ number_format($aging['days_60'] / 100, 2) }}</span>
                <span class="{{ $aging['days_90_plus'] > 0 ? 'font-medium text-red-600' : '' }}">90d+: KES {{ number_format($aging['days_90_plus'] / 100, 2) }}</span>
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

        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white print:border-0">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-left text-slate-500 print:bg-transparent">
                    <tr>
                        <th class="px-4 py-2">Date</th>
                        <th class="px-4 py-2">Type</th>
                        <th class="px-4 py-2">Reference</th>
                        <th class="px-4 py-2 text-right">Amount</th>
                        <th class="px-4 py-2 text-right">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($ledgerEntries as $entry)
                        <tr>
                            <td class="px-4 py-2 text-slate-500">{{ $entry->created_at->format('d M Y H:i') }}</td>
                            <td class="px-4 py-2 capitalize text-slate-700">{{ $entry->type }}</td>
                            <td class="px-4 py-2 text-slate-500">
                                {{ $entry->sale?->sale_number ?? $entry->notes ?? '—' }}
                            </td>
                            <td class="px-4 py-2 text-right {{ $entry->type === 'charge' ? 'text-red-600' : 'text-emerald-700' }}">
                                {{ $entry->type === 'charge' ? '+' : '-' }}KES {{ number_format($entry->amount_cents / 100, 2) }}
                            </td>
                            <td class="px-4 py-2 text-right text-slate-700">KES {{ number_format($entry->running_balance_cents / 100, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-400">No ledger history yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($showPaymentForm)
        <div class="fixed inset-0 z-10 flex items-center justify-center bg-slate-900/40 px-4 print:hidden" wire:click.self="$set('showPaymentForm', false)">
            <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-lg">
                <h2 class="mb-4 text-lg font-semibold">Record payment from {{ $customer->name }}</h2>
                <form wire:submit="recordPayment" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Amount (KES)</label>
                        <input type="number" step="0.01" wire:model="amount" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        @error('amount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Method</label>
                        <select wire:model.live="method" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                            <option value="cash">Cash</option>
                            <option value="mpesa">M-Pesa</option>
                        </select>
                    </div>
                    @if ($method === 'mpesa')
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">M-Pesa transaction code</label>
                            <input type="text" wire:model="mpesaCode" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                            @error('mpesaCode') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Notes (optional)</label>
                        <input type="text" wire:model="notes" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="$set('showPaymentForm', false)" class="rounded-md px-4 py-2 text-sm text-slate-600 hover:bg-slate-100">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                            Record payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <style>
        @media print {
            body * { visibility: hidden; }
            #statement, #statement * { visibility: visible; }
            #statement { position: absolute; top: 0; left: 0; width: 100%; }
        }
    </style>
</div>
