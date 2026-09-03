<div>
    @php $alertCount = $expiredBatches->count() + $lowStockProducts->count() + $expiringSoonBatches->count(); @endphp

    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-text-primary">Welcome, {{ auth()->user()->name }}</h1>
            <p class="mt-1 text-sm text-text-secondary">
                {{ now()->timezone('Africa/Nairobi')->translatedFormat('l, j F Y') }} &middot;
                @can('view-reports')
                    shop-wide summary for today.
                @else
                    your sales summary for today.
                @endcan
            </p>
        </div>
        @if ($alertCount > 0)
            <a
                href="#inventory-alerts"
                class="inline-flex items-center gap-1.5 rounded-full bg-danger-100 px-3 py-1.5 text-xs font-semibold text-danger-700 transition-colors hover:bg-danger-200"
            >
                <x-heroicon-o-exclamation-triangle class="h-3.5 w-3.5" />
                {{ $alertCount }} {{ $alertCount === 1 ? 'alert' : 'alerts' }}
            </a>
        @endif
    </div>

    {{-- Hero: today's headline number --}}
    <div class="mb-4">
        <x-stat-card-hero
            icon="banknotes"
            label="Sales today"
            value="KES {{ number_format($summary['net_revenue_cents'] / 100, 2) }}"
            :delta="$salesDelta"
            :sparkline="$heroSparkline"
        />
    </div>

    {{-- Secondary stats — compact tiles, dense grid even on mobile --}}
    <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        <x-stat-tile
            label="Transactions"
            value="{{ $summary['transaction_count'] }}"
            :delta="$transactionsDelta"
        />
        <x-stat-tile
            label="Cash"
            value="KES {{ number_format($summary['by_payment_method']['cash'] / 100, 2) }}"
        />
        <x-stat-tile
            label="M-Pesa"
            value="KES {{ number_format($summary['by_payment_method']['mpesa'] / 100, 2) }}"
        />
        <x-stat-tile
            label="Credit"
            value="KES {{ number_format($summary['by_payment_method']['credit'] / 100, 2) }}"
        />
        <x-stat-tile
            label="Discounts"
            variant="{{ $summary['discount_cents'] > 0 ? 'warn' : 'default' }}"
            value="KES {{ number_format($summary['discount_cents'] / 100, 2) }}"
        />
        @can('view-reports')
            <x-stat-tile
                label="Debtors"
                variant="{{ $outstandingDebtCents > 0 ? 'danger' : 'success' }}"
                value="KES {{ number_format($outstandingDebtCents / 100, 2) }}"
            />
        @endcan
    </div>

    @can('view-profit')
        <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
            <x-stat-tile
                label="Gross profit today"
                variant="{{ $summary['profit_cents'] >= 0 ? 'success' : 'danger' }}"
                value="KES {{ number_format($summary['profit_cents'] / 100, 2) }}"
            />
            <x-stat-tile
                label="Expenses today"
                variant="{{ $summary['expenses_cents'] > 0 ? 'warn' : 'default' }}"
                value="KES {{ number_format($summary['expenses_cents'] / 100, 2) }}"
            />
            <x-stat-tile
                label="Net profit today"
                variant="{{ $summary['net_profit_cents'] >= 0 ? 'success' : 'danger' }}"
                value="KES {{ number_format($summary['net_profit_cents'] / 100, 2) }}"
            />
        </div>
    @endcan

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        {{-- Sales overview chart --}}
        <x-card class="xl:col-span-2" title="Sales overview">
            <x-slot:actions>
                <div class="flex rounded-lg border border-surface-border p-0.5 text-xs font-medium">
                    <button
                        wire:click="setChartRange('today')"
                        @class(['rounded-md px-2.5 py-1 transition-colors', 'bg-primary-700 text-white' => $chartRange === 'today', 'text-text-secondary hover:bg-surface-muted' => $chartRange !== 'today'])
                    >
                        Today
                    </button>
                    <button
                        wire:click="setChartRange('week')"
                        @class(['rounded-md px-2.5 py-1 transition-colors', 'bg-primary-700 text-white' => $chartRange === 'week', 'text-text-secondary hover:bg-surface-muted' => $chartRange !== 'week'])
                    >
                        Week
                    </button>
                </div>
            </x-slot:actions>

            <div
                wire:ignore
                x-data="barChart(@js($chartData))"
                wire:key="sales-chart-{{ $chartRange }}"
                class="h-64"
            >
                <canvas x-ref="canvas"></canvas>
            </div>
        </x-card>

        {{-- Top products this week --}}
        <x-card title="Top products this week">
            <div class="-my-2 divide-y divide-surface-border">
                @forelse ($topProducts as $product)
                    <div class="flex items-center justify-between py-2.5 text-sm">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-text-primary">{{ $product->name }}</p>
                            <p class="text-xs text-text-muted">{{ $product->total_quantity }} {{ $product->base_unit }} sold</p>
                        </div>
                        <p class="font-tabular shrink-0 font-medium text-text-primary">
                            KES {{ number_format($product->total_cents / 100, 0) }}
                        </p>
                    </div>
                @empty
                    <x-empty-state icon="chart-bar" title="No sales yet this week" class="py-6" />
                @endforelse
            </div>
        </x-card>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        {{-- Inventory alerts --}}
        <x-card id="inventory-alerts" class="scroll-mt-4 xl:col-span-2" title="Inventory alerts">
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

        {{-- Recent transactions --}}
        <x-card title="Recent transactions">
            <div class="-my-2 divide-y divide-surface-border">
                @forelse ($recentSales as $sale)
                    <a href="{{ route('sales.receipt', $sale) }}" class="flex items-center gap-3 py-2.5 text-sm hover:bg-surface-muted">
                        @php
                            $primaryMethod = $sale->payments->first()?->method ?? 'cash';
                            $methodIcon = match ($primaryMethod) {
                                'mpesa' => 'device-phone-mobile',
                                'credit' => 'clock',
                                default => 'banknotes',
                            };
                        @endphp
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-surface-muted text-text-secondary">
                            <x-dynamic-component :component="'heroicon-o-'.$methodIcon" class="h-4 w-4" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-text-primary">{{ $sale->customer?->name ?? 'Walk-in' }}</p>
                            <p class="text-xs text-text-muted">{{ $sale->completed_at->format('H:i') }}</p>
                        </div>
                        <p class="font-tabular shrink-0 font-medium text-text-primary">KES {{ number_format($sale->total_cents / 100, 0) }}</p>
                    </a>
                @empty
                    <x-empty-state icon="receipt-percent" title="No sales yet today" class="py-6" />
                @endforelse
            </div>
        </x-card>
    </div>
</div>
