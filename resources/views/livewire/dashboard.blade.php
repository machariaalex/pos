<div>
    @php
        $alertCount = $expiredBatches->count() + $lowStockProducts->count() + $expiringSoonBatches->count();
        $nairobiHour = now()->timezone('Africa/Nairobi')->hour;
        $greeting = match (true) {
            $nairobiHour < 12 => 'Good morning',
            $nairobiHour < 17 => 'Good afternoon',
            default => 'Good evening',
        };
        $firstName = explode(' ', auth()->user()->name)[0];
    @endphp

    <div class="mb-5 flex flex-wrap items-end justify-between gap-3 animate-rise">
        <div>
            <h1 class="text-2xl font-semibold text-text-primary">{{ $greeting }}, {{ $firstName }}</h1>
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
            {{-- Mobile: alerts live on their own page. Desktop: scroll to the inline card below. --}}
            <a
                href="{{ route('inventory.alerts') }}"
                class="inline-flex items-center gap-1.5 rounded-full bg-danger-100 px-3 py-1.5 text-xs font-semibold text-danger-700 transition-colors hover:bg-danger-200 lg:hidden"
            >
                <x-heroicon-o-exclamation-triangle class="h-3.5 w-3.5" />
                {{ $alertCount }} {{ $alertCount === 1 ? 'alert' : 'alerts' }}
            </a>
            <a
                href="#inventory-alerts"
                class="hidden items-center gap-1.5 rounded-full bg-danger-100 px-3 py-1.5 text-xs font-semibold text-danger-700 transition-colors hover:bg-danger-200 lg:inline-flex"
            >
                <x-heroicon-o-exclamation-triangle class="h-3.5 w-3.5" />
                {{ $alertCount }} {{ $alertCount === 1 ? 'alert' : 'alerts' }}
            </a>
        @endif
    </div>

    {{-- Hero: today's headline number --}}
    <div class="mb-4 animate-rise" style="animation-delay: 60ms">
        <x-stat-card-hero
            icon="banknotes"
            label="Sales today"
            value="KES {{ number_format($summary['net_revenue_cents'] / 100, 2) }}"
            :delta="$salesDelta"
            :sparkline="$heroSparkline"
        />
    </div>

    {{-- Secondary stats — compact tiles, dense grid even on mobile --}}
    <div class="mb-4 grid grid-cols-2 gap-3 animate-rise sm:grid-cols-3 lg:grid-cols-6" style="animation-delay: 110ms">
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
        <div class="mb-6 grid grid-cols-1 gap-3 animate-rise sm:grid-cols-3" style="animation-delay: 150ms">
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

    @php
        $paymentMixTotal = array_sum($summary['by_payment_method']);
        $paymentMixLabels = ['Cash', 'M-Pesa', 'Credit'];
        $paymentMixValues = [
            $summary['by_payment_method']['cash'] / 100,
            $summary['by_payment_method']['mpesa'] / 100,
            $summary['by_payment_method']['credit'] / 100,
        ];
        $paymentMixColors = ['#2d7a48', '#2563eb', '#d97706'];
    @endphp

    <div class="grid grid-cols-1 gap-6 animate-rise xl:grid-cols-4" style="animation-delay: 190ms">
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

        {{-- Payment mix today --}}
        <x-card title="Payment mix today">
            @if ($paymentMixTotal > 0)
                <div wire:ignore x-data="doughnutChart(@js(['labels' => $paymentMixLabels, 'values' => $paymentMixValues]), @js($paymentMixColors))" wire:key="payment-mix-{{ $summary['date'] }}" class="h-36">
                    <canvas x-ref="canvas"></canvas>
                </div>
                <div class="mt-3 space-y-1.5">
                    @foreach ($paymentMixLabels as $i => $label)
                        <div class="flex items-center justify-between text-xs">
                            <span class="flex items-center gap-1.5 text-text-secondary">
                                <span class="h-2 w-2 rounded-full" style="background: {{ $paymentMixColors[$i] }}"></span>
                                {{ $label }}
                            </span>
                            <span class="font-tabular font-medium text-text-primary">{{ number_format($paymentMixValues[$i], 0) }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <x-empty-state icon="chart-pie" title="No payments yet today" class="py-6" />
            @endif
        </x-card>

        {{-- Top products this week --}}
        <x-card class="xl:col-span-1" title="Top products this week">
            @php $topProductsMax = $topProducts->max('total_cents') ?: 1; @endphp
            <div class="space-y-3">
                @forelse ($topProducts as $i => $product)
                    <div>
                        <div class="flex items-center justify-between gap-2 text-sm">
                            <div class="flex min-w-0 items-center gap-2">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary-100 text-[0.65rem] font-bold text-primary-700">{{ $i + 1 }}</span>
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-text-primary">{{ $product->name }}</p>
                                    <p class="text-xs text-text-muted">{{ $product->total_quantity }} {{ $product->base_unit }} sold</p>
                                </div>
                            </div>
                            <p class="font-tabular shrink-0 font-medium text-text-primary">
                                KES {{ number_format($product->total_cents / 100, 0) }}
                            </p>
                        </div>
                        <div class="mt-1.5 ml-7 h-1 rounded-full bg-surface-muted">
                            <div class="h-1 rounded-full bg-primary-500" style="width: {{ round($product->total_cents / $topProductsMax * 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <x-empty-state icon="chart-bar" title="No sales yet this week" class="py-6" />
                @endforelse
            </div>
        </x-card>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 animate-rise xl:grid-cols-3" style="animation-delay: 230ms">
        {{-- Inventory alerts: full detail inline on desktop; a compact
             summary linking to its own page on mobile (see the top-of-page
             alert badge too) — the same content either way, just where it
             lives differs by screen size. --}}
        @if ($alertCount > 0)
            <a href="{{ route('inventory.alerts') }}" class="block rounded-card border border-surface-border bg-surface-card p-4 shadow-sm lg:hidden">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-danger-100 text-danger-600">
                            <x-heroicon-o-exclamation-triangle class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-text-primary">Inventory alerts</p>
                            <p class="text-xs text-text-muted">{{ $alertCount }} {{ $alertCount === 1 ? 'item needs' : 'items need' }} attention</p>
                        </div>
                    </div>
                    <x-heroicon-o-chevron-right class="h-5 w-5 shrink-0 text-text-muted" />
                </div>
            </a>
        @else
            <div class="flex items-center gap-3 rounded-card border border-surface-border bg-surface-card p-4 shadow-sm lg:hidden">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-success-100 text-success-600">
                    <x-heroicon-o-check-circle class="h-5 w-5" />
                </div>
                <div>
                    <p class="text-sm font-semibold text-text-primary">Inventory alerts</p>
                    <p class="text-xs text-text-muted">All clear</p>
                </div>
            </div>
        @endif

        <x-card id="inventory-alerts" class="hidden scroll-mt-4 lg:block xl:col-span-2" title="Inventory alerts">
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

        {{-- Today's sales — what was actually sold, not just the total --}}
        <x-card title="Today's sales">
            <div class="-my-2 divide-y divide-surface-border">
                @forelse ($todaysSales as $sale)
                    @php
                        $primaryMethod = $sale->payments->first()?->method ?? 'cash';
                        [$methodIcon, $methodTint] = match ($primaryMethod) {
                            'mpesa' => ['device-phone-mobile', 'bg-info-100 text-info-600'],
                            'credit' => ['clock', 'bg-warn-100 text-warn-600'],
                            default => ['banknotes', 'bg-success-100 text-success-600'],
                        };
                        $itemsSummary = $sale->lines
                            ->map(fn ($line) => "{$line->quantity} {$line->product->base_unit} {$line->product->name}")
                            ->implode(', ');
                    @endphp
                    <a href="{{ route('sales.receipt', $sale) }}" class="flex items-center gap-3 py-2.5 text-sm hover:bg-surface-muted">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $methodTint }}">
                            <x-dynamic-component :component="'heroicon-o-'.$methodIcon" class="h-4 w-4" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <p class="truncate font-medium text-text-primary">{{ $sale->customer?->name ?? 'Walk-in' }}</p>
                                <p class="font-tabular shrink-0 font-medium text-text-primary">KES {{ number_format($sale->total_cents / 100, 0) }}</p>
                            </div>
                            <p class="truncate text-xs text-text-muted">{{ $itemsSummary }} &middot; {{ $sale->completed_at->format('H:i') }}</p>
                        </div>
                    </a>
                @empty
                    <x-empty-state icon="receipt-percent" title="No sales yet today" class="py-6" />
                @endforelse
            </div>
        </x-card>
    </div>
</div>
