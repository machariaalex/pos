<div>
    <div class="mb-6">
        <a href="{{ route('reports.index') }}" class="text-sm text-slate-500 hover:underline">&larr; Reports</a>
        <h1 class="mt-1 text-2xl font-semibold text-slate-900">Fast &amp; slow movers</h1>
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
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div>
            <h2 class="mb-3 text-lg font-semibold text-slate-900">Fast movers</h2>
            <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-2">Product</th>
                            <th class="px-4 py-2 text-right">Sold</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($fastMovers as $row)
                            <tr>
                                <td class="px-4 py-2 text-slate-700">{{ $row->name }}</td>
                                <td class="px-4 py-2 text-right text-slate-600">{{ $row->total_quantity }} {{ $row->base_unit }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <h2 class="mb-3 text-lg font-semibold text-slate-900">Slow movers</h2>
            <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-2">Product</th>
                            <th class="px-4 py-2 text-right">Sold</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($slowMovers as $row)
                            <tr>
                                <td class="px-4 py-2 text-slate-700">{{ $row->name }}</td>
                                <td class="px-4 py-2 text-right {{ $row->total_quantity == 0 ? 'text-red-600' : 'text-slate-600' }}">
                                    {{ $row->total_quantity }} {{ $row->base_unit }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
