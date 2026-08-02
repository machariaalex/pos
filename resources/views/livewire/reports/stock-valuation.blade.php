<div>
    <div class="mb-6">
        <a href="{{ route('reports.index') }}" class="text-sm text-slate-500 hover:underline">&larr; Reports</a>
        <h1 class="mt-1 text-2xl font-semibold text-slate-900">Stock valuation</h1>
        <p class="text-sm text-slate-500">Current stock on hand, valued at buying price.</p>
    </div>

    <div class="mb-6 rounded-lg border border-slate-200 bg-white p-4">
        <p class="text-sm text-slate-500">Total stock value</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">KES {{ number_format($total / 100, 2) }}</p>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-2">Category</th>
                    <th class="px-4 py-2 text-right">Value</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($byCategory as $category => $value)
                    <tr>
                        <td class="px-4 py-2 text-slate-700">{{ $category }}</td>
                        <td class="px-4 py-2 text-right font-medium text-slate-900">{{ number_format($value / 100, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="px-4 py-8 text-center text-slate-400">No stock on hand.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
