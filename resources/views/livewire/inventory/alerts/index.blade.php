<div>
    <div class="mb-6">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 text-sm text-text-muted hover:text-text-primary hover:underline">
            <x-heroicon-o-arrow-left class="h-4 w-4" /> Dashboard
        </a>
        <h1 class="mt-1 text-2xl font-semibold text-text-primary">Inventory alerts</h1>
    </div>

    <x-card :padding="true">
        <div class="space-y-4">
            @if ($expiredBatches->isNotEmpty())
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-text-muted">Expired</p>
                    <div class="space-y-1.5">
                        @foreach ($expiredBatches as $batch)
                            <x-alert-row variant="danger" icon="x-circle" class="border-l-2 border-danger-600">
                                <p class="truncate text-sm font-medium text-text-primary">{{ $batch->product->name }}</p>
                                <p class="text-xs text-text-secondary">Batch {{ $batch->batch_number }} &middot; expired {{ $batch->expiry_date->toDateString() }} &middot; {{ $batch->quantity_remaining }} {{ $batch->product->base_unit }} left</p>
                                <x-slot:action>
                                    <x-button :href="route('inventory.products.show', $batch->product)" variant="danger" size="sm">Write off</x-button>
                                </x-slot:action>
                            </x-alert-row>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($lowStockProducts->isNotEmpty())
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-text-muted">Low stock</p>
                    <div class="space-y-1.5">
                        @foreach ($lowStockProducts as $product)
                            <x-alert-row variant="danger" icon="arrow-trending-down" class="border-l-2 border-danger-600">
                                <p class="truncate text-sm font-medium text-text-primary">{{ $product->name }}</p>
                                <p class="text-xs text-text-secondary">{{ $product->stockOnHand() }} {{ $product->base_unit }} left &middot; reorder at {{ $product->reorder_level }}</p>
                                <x-slot:action>
                                    <x-button :href="route('inventory.products.show', $product)" variant="secondary" size="sm">View</x-button>
                                </x-slot:action>
                            </x-alert-row>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($expiringSoonBatches->isNotEmpty())
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-text-muted">Expiring within 60 days</p>
                    <div class="space-y-1.5">
                        @foreach ($expiringSoonBatches as $batch)
                            <x-alert-row variant="danger" icon="clock" class="border-l-2 border-danger-600">
                                <p class="truncate text-sm font-medium text-text-primary">{{ $batch->product->name }}</p>
                                <p class="text-xs text-text-secondary">Batch {{ $batch->batch_number }} &middot; expires {{ $batch->expiry_date->toDateString() }} &middot; {{ $batch->quantity_remaining }} {{ $batch->product->base_unit }} left</p>
                                <x-slot:action>
                                    <x-button :href="route('inventory.products.show', $batch->product)" variant="secondary" size="sm">View</x-button>
                                </x-slot:action>
                            </x-alert-row>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($expiredBatches->isEmpty() && $lowStockProducts->isEmpty() && $expiringSoonBatches->isEmpty())
                <x-empty-state icon="check-circle" title="All clear" description="No low stock, expired, or soon-to-expire batches." />
            @endif
        </div>
    </x-card>
</div>
