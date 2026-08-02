<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-slate-900">Stock takes</h1>
        <button wire:click="start" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
            Start new stock take
        </button>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-2">Reference</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Started by</th>
                    <th class="px-4 py-2">Started</th>
                    <th class="px-4 py-2">Completed</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($stockTakes as $stockTake)
                    <tr>
                        <td class="px-4 py-2 font-medium text-slate-700">{{ $stockTake->reference }}</td>
                        <td class="px-4 py-2">
                            <span class="rounded px-2 py-0.5 text-xs {{ $stockTake->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ str($stockTake->status)->headline() }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-slate-600">{{ $stockTake->startedBy->name }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $stockTake->started_at->format('d M Y H:i') }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $stockTake->completed_at?->format('d M Y H:i') ?? '—' }}</td>
                        <td class="px-4 py-2 text-right">
                            <a href="{{ route('inventory.stock-takes.show', $stockTake) }}" class="text-emerald-700 hover:underline">
                                {{ $stockTake->status === 'completed' ? 'View' : 'Continue' }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-400">No stock takes yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $stockTakes->links() }}
    </div>
</div>
