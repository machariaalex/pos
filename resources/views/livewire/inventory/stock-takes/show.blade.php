<div>
    <div class="mb-6">
        <a href="{{ route('inventory.stock-takes.index') }}" class="text-sm text-slate-500 hover:underline">&larr; Stock takes</a>
        <div class="mt-1 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">{{ $stockTake->reference }}</h1>
                <p class="text-sm text-slate-500">
                    Started by {{ $stockTake->startedBy->name }} on {{ $stockTake->started_at->format('d M Y H:i') }}
                </p>
            </div>
            @if ($stockTake->status === 'in_progress')
                <div class="flex gap-3">
                    <button wire:click="saveCounts" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Save progress
                    </button>
                    <button
                        wire:click="complete"
                        wire:confirm="Apply corrections for all counted lines and close this stock take?"
                        class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700"
                    >
                        Complete &amp; apply corrections
                    </button>
                </div>
            @else
                <span class="rounded bg-emerald-100 px-3 py-1 text-sm text-emerald-700">Completed</span>
            @endif
        </div>
    </div>

    @if ($completionSummary)
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            Stock take complete: {{ $completionSummary['lines_counted'] }} lines counted,
            {{ $completionSummary['corrections'] }} corrections applied.
        </div>
    @endif

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-2">Product</th>
                    <th class="px-4 py-2">Batch</th>
                    <th class="px-4 py-2">System qty</th>
                    <th class="px-4 py-2">Counted qty</th>
                    <th class="px-4 py-2">Variance</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($lines as $line)
                    <tr>
                        <td class="px-4 py-2 text-slate-700">{{ $line->batch->product->name }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $line->batch->batch_number }}</td>
                        <td class="px-4 py-2 text-slate-600">{{ $line->system_quantity }} {{ $line->batch->product->base_unit }}</td>
                        <td class="px-4 py-2">
                            @if ($stockTake->status === 'in_progress')
                                <input
                                    type="number"
                                    step="0.001"
                                    wire:model="counts.{{ $line->id }}"
                                    placeholder="Not counted"
                                    class="w-28 rounded-md border border-slate-300 px-2 py-1 text-sm"
                                >
                            @else
                                {{ $line->counted_quantity ?? '—' }}
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            @php $variance = $line->variance(); @endphp
                            @if ($variance === null)
                                <span class="text-slate-400">—</span>
                            @elseif (bccomp($variance, '0', 3) === 0)
                                <span class="text-slate-500">0</span>
                            @else
                                <span class="font-medium {{ bccomp($variance, '0', 3) > 0 ? 'text-emerald-700' : 'text-red-600' }}">
                                    {{ bccomp($variance, '0', 3) > 0 ? '+' : '' }}{{ $variance }}
                                </span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
