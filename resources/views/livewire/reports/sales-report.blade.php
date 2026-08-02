<div>
    <div class="mb-6">
        <a href="{{ route('reports.index') }}" class="text-sm text-slate-500 hover:underline">&larr; Reports</a>
        <h1 class="mt-1 text-2xl font-semibold text-slate-900">Sales report</h1>
    </div>

    <div class="mb-6 flex flex-wrap items-end gap-4">
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">From</label>
            <input type="date" wire:model.live="dateFrom" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">To</label>
            <input type="date" wire:model.live="dateTo" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Group by</label>
            <select wire:model.live="groupBy" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="none">None (totals only)</option>
                <option value="product">Product</option>
                <option value="category">Category</option>
                <option value="attendant">Attendant</option>
            </select>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-2">
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-sm text-slate-500">Total sales</p>
            <p class="mt-1 text-lg font-semibold text-slate-900">KES {{ number_format($totals['total_cents'] / 100, 2) }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-sm text-slate-500">Transactions</p>
            <p class="mt-1 text-lg font-semibold text-slate-900">{{ $totals['transaction_count'] }}</p>
        </div>
    </div>

    @if ($groupBy !== 'none')
        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                    <tr>
                        @if ($groupBy === 'product')
                            <th class="px-4 py-2">Product</th>
                            <th class="px-4 py-2 text-right">Quantity sold</th>
                            <th class="px-4 py-2 text-right">Revenue</th>
                        @elseif ($groupBy === 'category')
                            <th class="px-4 py-2">Category</th>
                            <th class="px-4 py-2 text-right">Revenue</th>
                        @elseif ($groupBy === 'attendant')
                            <th class="px-4 py-2">Attendant</th>
                            <th class="px-4 py-2 text-right">Transactions</th>
                            <th class="px-4 py-2 text-right">Revenue</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($breakdown as $row)
                        <tr>
                            @if ($groupBy === 'product')
                                <td class="px-4 py-2 text-slate-700">{{ $row->name }}</td>
                                <td class="px-4 py-2 text-right text-slate-600">{{ $row->total_quantity }} {{ $row->base_unit }}</td>
                                <td class="px-4 py-2 text-right font-medium text-slate-900">{{ number_format($row->total_cents / 100, 2) }}</td>
                            @elseif ($groupBy === 'category')
                                <td class="px-4 py-2 text-slate-700">{{ $row->name }}</td>
                                <td class="px-4 py-2 text-right font-medium text-slate-900">{{ number_format($row->total_cents / 100, 2) }}</td>
                            @elseif ($groupBy === 'attendant')
                                <td class="px-4 py-2 text-slate-700">{{ $row->name }}</td>
                                <td class="px-4 py-2 text-right text-slate-600">{{ $row->transaction_count }}</td>
                                <td class="px-4 py-2 text-right font-medium text-slate-900">{{ number_format($row->total_cents / 100, 2) }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-slate-400">No sales in this range.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
