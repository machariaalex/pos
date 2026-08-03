<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-text-primary">Stock takes</h1>
        <x-button variant="primary" wire:click="start">
            <x-heroicon-o-plus class="h-4 w-4" />
            Start new stock take
        </x-button>
    </div>

    <x-table>
        <thead>
            <tr>
                <x-table.th>Reference</x-table.th>
                <x-table.th>Status</x-table.th>
                <x-table.th>Started by</x-table.th>
                <x-table.th>Started</x-table.th>
                <x-table.th>Completed</x-table.th>
                <x-table.th></x-table.th>
            </tr>
        </thead>
        <tbody>
            @forelse ($stockTakes as $stockTake)
                <tr class="hover:bg-surface-muted/50">
                    <x-table.td>
                        <span class="font-medium text-text-primary">{{ $stockTake->reference }}</span>
                    </x-table.td>
                    <x-table.td>
                        <x-badge :variant="$stockTake->status === 'completed' ? 'success' : 'warn'">
                            {{ str($stockTake->status)->headline() }}
                        </x-badge>
                    </x-table.td>
                    <x-table.td>
                        <span class="text-text-secondary">{{ $stockTake->startedBy->name }}</span>
                    </x-table.td>
                    <x-table.td>
                        <span class="text-text-muted">{{ $stockTake->started_at->format('d M Y H:i') }}</span>
                    </x-table.td>
                    <x-table.td>
                        <span class="text-text-muted">{{ $stockTake->completed_at?->format('d M Y H:i') ?? '—' }}</span>
                    </x-table.td>
                    <x-table.td align="right">
                        <x-button variant="ghost" size="sm" :href="route('inventory.stock-takes.show', $stockTake)">
                            {{ $stockTake->status === 'completed' ? 'View' : 'Continue' }}
                        </x-button>
                    </x-table.td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <x-empty-state icon="clipboard-document-list" title="No stock takes yet" description="Start a stock take to count and reconcile your inventory." />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>

    <div class="mt-4">
        {{ $stockTakes->links() }}
    </div>
</div>
