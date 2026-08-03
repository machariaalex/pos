<div>
    <div class="mb-6">
        <a href="{{ route('inventory.stock-takes.index') }}" class="inline-flex items-center gap-1 text-sm text-text-muted hover:text-text-secondary">
            <x-heroicon-o-arrow-left class="h-3.5 w-3.5" />
            Stock takes
        </a>
        <div class="mt-1 flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-text-primary">{{ $stockTake->reference }}</h1>
                <p class="text-sm text-text-muted">
                    Started by {{ $stockTake->startedBy->name }} on {{ $stockTake->started_at->format('d M Y H:i') }}
                </p>
            </div>
            @if ($stockTake->status === 'in_progress')
                <div class="flex shrink-0 gap-3">
                    <x-button variant="secondary" wire:click="saveCounts">
                        <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                        Save progress
                    </x-button>
                    <x-button
                        variant="primary"
                        wire:click="complete"
                        wire:confirm="Apply corrections for all counted lines and close this stock take?"
                    >
                        <x-heroicon-o-check class="h-4 w-4" />
                        Complete &amp; apply corrections
                    </x-button>
                </div>
            @else
                <x-badge variant="success">Completed</x-badge>
            @endif
        </div>
    </div>

    @if ($completionSummary)
        <x-alert-row variant="success" icon="check-circle" class="mb-6">
            <p class="text-sm font-medium text-success-700">
                Stock take complete: {{ $completionSummary['lines_counted'] }} lines counted,
                {{ $completionSummary['corrections'] }} corrections applied.
            </p>
        </x-alert-row>
    @endif

    <x-table>
        <thead>
            <tr>
                <x-table.th>Product</x-table.th>
                <x-table.th>Batch</x-table.th>
                <x-table.th align="right">System qty</x-table.th>
                <x-table.th align="right">Counted qty</x-table.th>
                <x-table.th align="right">Variance</x-table.th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lines as $line)
                <tr class="hover:bg-surface-muted/50">
                    <x-table.td>{{ $line->batch->product->name }}</x-table.td>
                    <x-table.td>
                        <span class="text-text-muted">{{ $line->batch->batch_number }}</span>
                    </x-table.td>
                    <x-table.td align="right">
                        <span class="font-tabular text-text-secondary">{{ $line->system_quantity }} {{ $line->batch->product->base_unit }}</span>
                    </x-table.td>
                    <x-table.td align="right">
                        @if ($stockTake->status === 'in_progress')
                            <input
                                type="number"
                                step="0.001"
                                wire:model="counts.{{ $line->id }}"
                                placeholder="Not counted"
                                class="w-28 rounded-lg border border-surface-border bg-surface-card px-2 py-1 text-right text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary-700/30"
                            >
                        @else
                            <span class="font-tabular text-text-secondary">{{ $line->counted_quantity ?? '—' }}</span>
                        @endif
                    </x-table.td>
                    <x-table.td align="right">
                        @php $variance = $line->variance(); @endphp
                        @if ($variance === null)
                            <span class="text-text-muted">—</span>
                        @elseif (bccomp($variance, '0', 3) === 0)
                            <span class="font-tabular text-text-muted">0</span>
                        @else
                            <span class="font-tabular font-semibold {{ bccomp($variance, '0', 3) > 0 ? 'text-success-600' : 'text-danger-600' }}">
                                {{ bccomp($variance, '0', 3) > 0 ? '+' : '' }}{{ $variance }}
                            </span>
                        @endif
                    </x-table.td>
                </tr>
            @endforeach
        </tbody>
    </x-table>
</div>
