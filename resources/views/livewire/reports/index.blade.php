<div>
    <h1 class="mb-6 text-2xl font-semibold text-text-primary">Reports</h1>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('reports.sales') }}" class="group rounded-card border border-surface-border bg-surface-card p-5 shadow-sm transition-all hover:border-primary-700/40 hover:shadow-md">
            <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-primary-100 text-primary-700">
                <x-heroicon-o-chart-bar class="h-5 w-5" />
            </div>
            <h2 class="font-semibold text-text-primary group-hover:text-primary-700">Sales</h2>
            <p class="mt-1 text-sm text-text-muted">By date range, product, category, or attendant.</p>
        </a>

        @can('view-profit')
            <a href="{{ route('reports.profit') }}" class="group rounded-card border border-surface-border bg-surface-card p-5 shadow-sm transition-all hover:border-primary-700/40 hover:shadow-md">
                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-success-100 text-success-600">
                    <x-heroicon-o-currency-dollar class="h-5 w-5" />
                </div>
                <h2 class="font-semibold text-text-primary group-hover:text-primary-700">Profit</h2>
                <p class="mt-1 text-sm text-text-muted">Revenue minus cost of goods sold. Owner only.</p>
            </a>
        @endcan

        @can('view-buying-price')
            <a href="{{ route('reports.stock-valuation') }}" class="group rounded-card border border-surface-border bg-surface-card p-5 shadow-sm transition-all hover:border-primary-700/40 hover:shadow-md">
                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-info-100 text-info-600">
                    <x-heroicon-o-cube class="h-5 w-5" />
                </div>
                <h2 class="font-semibold text-text-primary group-hover:text-primary-700">Stock valuation</h2>
                <p class="mt-1 text-sm text-text-muted">Current stock on hand at buying price.</p>
            </a>
        @endcan

        <a href="{{ route('reports.fast-slow-movers') }}" class="group rounded-card border border-surface-border bg-surface-card p-5 shadow-sm transition-all hover:border-primary-700/40 hover:shadow-md">
            <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-warn-100 text-warn-600">
                <x-heroicon-o-arrow-trending-up class="h-5 w-5" />
            </div>
            <h2 class="font-semibold text-text-primary group-hover:text-primary-700">Fast &amp; slow movers</h2>
            <p class="mt-1 text-sm text-text-muted">Which products sell, which sit on the shelf.</p>
        </a>

        <a href="{{ route('customers.debtors') }}" class="group rounded-card border border-surface-border bg-surface-card p-5 shadow-sm transition-all hover:border-primary-700/40 hover:shadow-md">
            <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-danger-100 text-danger-600">
                <x-heroicon-o-users class="h-5 w-5" />
            </div>
            <h2 class="font-semibold text-text-primary group-hover:text-primary-700">Debtors</h2>
            <p class="mt-1 text-sm text-text-muted">Outstanding customer balances by age.</p>
        </a>

        <a href="{{ route('reports.expiry') }}" class="group rounded-card border border-surface-border bg-surface-card p-5 shadow-sm transition-all hover:border-primary-700/40 hover:shadow-md">
            <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-warn-100 text-warn-600">
                <x-heroicon-o-calendar class="h-5 w-5" />
            </div>
            <h2 class="font-semibold text-text-primary group-hover:text-primary-700">Expiry</h2>
            <p class="mt-1 text-sm text-text-muted">Full expired and expiring-soon batch list.</p>
        </a>

        @can('manage-expenses')
            <a href="{{ route('reports.expenses') }}" class="group rounded-card border border-surface-border bg-surface-card p-5 shadow-sm transition-all hover:border-primary-700/40 hover:shadow-md">
                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-danger-100 text-danger-600">
                    <x-heroicon-o-arrow-trending-down class="h-5 w-5" />
                </div>
                <h2 class="font-semibold text-text-primary group-hover:text-primary-700">Expenses</h2>
                <p class="mt-1 text-sm text-text-muted">By date range, or broken down by category.</p>
            </a>
        @endcan

        <a href="{{ route('cash-up.index') }}" class="group rounded-card border border-surface-border bg-surface-card p-5 shadow-sm transition-all hover:border-primary-700/40 hover:shadow-md">
            <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-primary-100 text-primary-700">
                <x-heroicon-o-banknotes class="h-5 w-5" />
            </div>
            <h2 class="font-semibold text-text-primary group-hover:text-primary-700">Cash-ups</h2>
            <p class="mt-1 text-sm text-text-muted">Declared drawer cash and variance history.</p>
        </a>

        @can('view-audit-log')
            <a href="{{ route('audit-log.index') }}" class="group rounded-card border border-surface-border bg-surface-card p-5 shadow-sm transition-all hover:border-primary-700/40 hover:shadow-md">
                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-surface-muted text-text-secondary">
                    <x-heroicon-o-clipboard-document-list class="h-5 w-5" />
                </div>
                <h2 class="font-semibold text-text-primary group-hover:text-primary-700">Audit log</h2>
                <p class="mt-1 text-sm text-text-muted">Every sensitive action, filterable. Owner only.</p>
            </a>
        @endcan
    </div>
</div>
