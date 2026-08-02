<div>
    <h1 class="mb-6 text-2xl font-semibold text-slate-900">Reports</h1>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('reports.sales') }}" class="rounded-lg border border-slate-200 bg-white p-4 hover:border-emerald-300 hover:shadow-sm">
            <h2 class="font-semibold text-slate-900">Sales</h2>
            <p class="mt-1 text-sm text-slate-500">By date range, product, category, or attendant.</p>
        </a>

        @can('view-profit')
            <a href="{{ route('reports.profit') }}" class="rounded-lg border border-slate-200 bg-white p-4 hover:border-emerald-300 hover:shadow-sm">
                <h2 class="font-semibold text-slate-900">Profit</h2>
                <p class="mt-1 text-sm text-slate-500">Revenue minus cost of goods sold. Owner only.</p>
            </a>
        @endcan

        @can('view-buying-price')
            <a href="{{ route('reports.stock-valuation') }}" class="rounded-lg border border-slate-200 bg-white p-4 hover:border-emerald-300 hover:shadow-sm">
                <h2 class="font-semibold text-slate-900">Stock valuation</h2>
                <p class="mt-1 text-sm text-slate-500">Current stock on hand at buying price.</p>
            </a>
        @endcan

        <a href="{{ route('reports.fast-slow-movers') }}" class="rounded-lg border border-slate-200 bg-white p-4 hover:border-emerald-300 hover:shadow-sm">
            <h2 class="font-semibold text-slate-900">Fast &amp; slow movers</h2>
            <p class="mt-1 text-sm text-slate-500">Which products sell, which sit on the shelf.</p>
        </a>

        <a href="{{ route('customers.debtors') }}" class="rounded-lg border border-slate-200 bg-white p-4 hover:border-emerald-300 hover:shadow-sm">
            <h2 class="font-semibold text-slate-900">Debtors</h2>
            <p class="mt-1 text-sm text-slate-500">Outstanding customer balances by age.</p>
        </a>

        <a href="{{ route('reports.expiry') }}" class="rounded-lg border border-slate-200 bg-white p-4 hover:border-emerald-300 hover:shadow-sm">
            <h2 class="font-semibold text-slate-900">Expiry</h2>
            <p class="mt-1 text-sm text-slate-500">Full expired and expiring-soon batch list.</p>
        </a>

        <a href="{{ route('cash-up.index') }}" class="rounded-lg border border-slate-200 bg-white p-4 hover:border-emerald-300 hover:shadow-sm">
            <h2 class="font-semibold text-slate-900">Cash-ups</h2>
            <p class="mt-1 text-sm text-slate-500">Declared drawer cash and variance history.</p>
        </a>

        @can('view-audit-log')
            <a href="{{ route('audit-log.index') }}" class="rounded-lg border border-slate-200 bg-white p-4 hover:border-emerald-300 hover:shadow-sm">
                <h2 class="font-semibold text-slate-900">Audit log</h2>
                <p class="mt-1 text-sm text-slate-500">Every sensitive action, filterable. Owner only.</p>
            </a>
        @endcan
    </div>
</div>
