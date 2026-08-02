<div>
    <div class="mb-6 flex items-center justify-between print:hidden">
        <h1 class="text-2xl font-semibold text-slate-900">Debtors report</h1>
        <button onclick="window.print()" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Print
        </button>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-5">
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-sm text-slate-500">Total outstanding</p>
            <p class="mt-1 text-lg font-semibold text-slate-900">KES {{ number_format($grandTotal / 100, 2) }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-sm text-slate-500">Current</p>
            <p class="mt-1 text-lg font-semibold text-slate-900">KES {{ number_format($totals['current'] / 100, 2) }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-sm text-slate-500">30 days</p>
            <p class="mt-1 text-lg font-semibold text-amber-600">KES {{ number_format($totals['days_30'] / 100, 2) }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-sm text-slate-500">60 days</p>
            <p class="mt-1 text-lg font-semibold text-orange-600">KES {{ number_format($totals['days_60'] / 100, 2) }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-sm text-slate-500">90+ days</p>
            <p class="mt-1 text-lg font-semibold text-red-600">KES {{ number_format($totals['days_90_plus'] / 100, 2) }}</p>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-2">Customer</th>
                    <th class="px-4 py-2">Phone</th>
                    <th class="px-4 py-2 text-right">Current</th>
                    <th class="px-4 py-2 text-right">30 days</th>
                    <th class="px-4 py-2 text-right">60 days</th>
                    <th class="px-4 py-2 text-right">90+ days</th>
                    <th class="px-4 py-2 text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($debtors as $row)
                    <tr>
                        <td class="px-4 py-2">
                            <a href="{{ route('customers.show', $row['customer']) }}" class="font-medium text-emerald-700 hover:underline">
                                {{ $row['customer']->name }}
                            </a>
                        </td>
                        <td class="px-4 py-2 text-slate-500">{{ $row['customer']->phone }}</td>
                        <td class="px-4 py-2 text-right text-slate-600">{{ number_format($row['aging']['current'] / 100, 2) }}</td>
                        <td class="px-4 py-2 text-right text-amber-600">{{ number_format($row['aging']['days_30'] / 100, 2) }}</td>
                        <td class="px-4 py-2 text-right text-orange-600">{{ number_format($row['aging']['days_60'] / 100, 2) }}</td>
                        <td class="px-4 py-2 text-right {{ $row['aging']['days_90_plus'] > 0 ? 'font-medium text-red-600' : 'text-slate-400' }}">
                            {{ number_format($row['aging']['days_90_plus'] / 100, 2) }}
                        </td>
                        <td class="px-4 py-2 text-right font-medium text-slate-900">
                            {{ number_format($row['customer']->balance_cents / 100, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-400">No outstanding debtors.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
