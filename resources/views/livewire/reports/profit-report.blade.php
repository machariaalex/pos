<div>
    <div class="mb-6">
        <a href="{{ route('reports.index') }}" class="inline-flex items-center gap-1 text-sm text-text-muted hover:text-text-secondary">
            <x-heroicon-o-arrow-left class="h-3.5 w-3.5" />
            Reports
        </a>
        <h1 class="mt-1 text-2xl font-semibold text-text-primary">Profit report</h1>
        <p class="text-sm text-text-muted">Owner only &mdash; revenue minus cost of goods sold, net of returns.</p>
    </div>

    <div class="mb-6 flex flex-wrap items-end gap-4">
        <div>
            <label class="mb-1 block text-sm font-medium text-text-primary">From</label>
            <input type="date" wire:model.live="dateFrom" class="rounded-lg border border-surface-border bg-surface-card px-3 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary-700/30">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-text-primary">To</label>
            <input type="date" wire:model.live="dateTo" class="rounded-lg border border-surface-border bg-surface-card px-3 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary-700/30">
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <x-stat-card
            icon="banknotes"
            variant="primary"
            label="Revenue"
            :value="'KES '.number_format($revenue_cents / 100, 2)"
        />
        <x-stat-card
            icon="arrow-trending-down"
            variant="warn"
            label="Cost of goods sold"
            :value="'KES '.number_format($cogs_cents / 100, 2)"
        />
        <x-stat-card
            icon="currency-dollar"
            :variant="$profit_cents >= 0 ? 'success' : 'danger'"
            label="Profit"
            :value="'KES '.number_format($profit_cents / 100, 2)"
        />
        <x-stat-card
            icon="percent-badge"
            variant="info"
            label="Margin"
            :value="$margin_percent.'%'"
        />
    </div>
</div>
