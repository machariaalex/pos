<div>
    <div class="mb-6">
        <a href="{{ route('reports.index') }}" class="text-sm text-slate-500 hover:underline">&larr; Reports</a>
        <h1 class="mt-1 text-2xl font-semibold text-slate-900">Expiry report</h1>
    </div>

    <div class="mb-4 flex gap-2">
        <button wire:click="$set('filter', 'all')" class="rounded-md px-3 py-1.5 text-sm {{ $filter === 'all' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600' }}">All</button>
        <button wire:click="$set('filter', 'expired')" class="rounded-md px-3 py-1.5 text-sm {{ $filter === 'expired' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600' }}">Expired</button>
        <button wire:click="$set('filter', 'expiring_soon')" class="rounded-md px-3 py-1.5 text-sm {{ $filter === 'expiring_soon' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600' }}">Expiring &le;60 days</button>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-2">Product</th>
                    <th class="px-4 py-2">Batch</th>
                    <th class="px-4 py-2">Expiry</th>
                    <th class="px-4 py-2 text-right">Remaining</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($batches as $batch)
                    <tr>
                        <td class="px-4 py-2">
                            <a href="{{ route('inventory.products.show', $batch->product) }}" class="font-medium text-emerald-700 hover:underline">
                                {{ $batch->product->name }}
                            </a>
                        </td>
                        <td class="px-4 py-2 text-slate-600">{{ $batch->batch_number }}</td>
                        <td class="px-4 py-2">
                            @if ($batch->isExpired())
                                <span class="rounded bg-red-100 px-1.5 py-0.5 text-xs text-red-700">Expired {{ $batch->expiry_date->toDateString() }}</span>
                            @elseif ($batch->isExpiringSoon())
                                <span class="rounded bg-amber-100 px-1.5 py-0.5 text-xs text-amber-700">{{ $batch->expiry_date->toDateString() }}</span>
                            @else
                                <span class="text-slate-600">{{ $batch->expiry_date->toDateString() }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right text-slate-700">{{ $batch->quantity_remaining }} {{ $batch->product->base_unit }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-slate-400">No batches match this filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
