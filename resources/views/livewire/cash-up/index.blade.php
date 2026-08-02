<div>
    <h1 class="mb-6 text-2xl font-semibold text-slate-900">Cash-up</h1>

    <div class="mb-6 max-w-md rounded-lg border border-slate-200 bg-white p-4">
        <h2 class="mb-3 font-semibold text-slate-900">Today's drawer</h2>
        <p class="mb-3 text-sm text-slate-500">
            Expected cash from your sales today: <span class="font-medium text-slate-700">KES {{ number_format($expectedToday / 100, 2) }}</span>
        </p>

        <form wire:submit="declare" class="space-y-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Cash counted in drawer (KES)</label>
                <input type="number" step="0.01" wire:model="declaredAmount" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                @error('declaredAmount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Notes (optional)</label>
                <input type="text" wire:model="notes" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </div>
            <button type="submit" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                {{ $todaysCashUp ? 'Update declaration' : 'Declare' }}
            </button>
        </form>

        @if ($todaysCashUp)
            <div class="mt-4 rounded-md bg-slate-50 p-3 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Expected</span><span>KES {{ number_format($todaysCashUp->expected_cash_cents / 100, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Declared</span><span>KES {{ number_format($todaysCashUp->declared_cash_cents / 100, 2) }}</span></div>
                <div class="flex justify-between font-medium {{ $todaysCashUp->variance_cents == 0 ? 'text-emerald-700' : 'text-red-600' }}">
                    <span>Variance</span><span>KES {{ number_format($todaysCashUp->variance_cents / 100, 2) }}</span>
                </div>
            </div>
        @endif
    </div>

    <h2 class="mb-3 text-lg font-semibold text-slate-900">History</h2>
    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-2">Date</th>
                    @can('view-reports')
                        <th class="px-4 py-2">Attendant</th>
                    @endcan
                    <th class="px-4 py-2 text-right">Expected</th>
                    <th class="px-4 py-2 text-right">Declared</th>
                    <th class="px-4 py-2 text-right">Variance</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($history as $cashUp)
                    <tr>
                        <td class="px-4 py-2 text-slate-600">{{ $cashUp->business_date->toDateString() }}</td>
                        @can('view-reports')
                            <td class="px-4 py-2 text-slate-600">{{ $cashUp->user->name }}</td>
                        @endcan
                        <td class="px-4 py-2 text-right text-slate-600">{{ number_format($cashUp->expected_cash_cents / 100, 2) }}</td>
                        <td class="px-4 py-2 text-right text-slate-600">{{ number_format($cashUp->declared_cash_cents / 100, 2) }}</td>
                        <td class="px-4 py-2 text-right font-medium {{ $cashUp->variance_cents == 0 ? 'text-emerald-700' : 'text-red-600' }}">
                            {{ number_format($cashUp->variance_cents / 100, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-400">No cash-ups declared yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
