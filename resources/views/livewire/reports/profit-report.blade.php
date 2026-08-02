<div>
    <div class="mb-6">
        <a href="{{ route('reports.index') }}" class="text-sm text-slate-500 hover:underline">&larr; Reports</a>
        <h1 class="mt-1 text-2xl font-semibold text-slate-900">Profit report</h1>
        <p class="text-sm text-slate-500">Owner only &mdash; revenue minus cost of goods sold, net of returns.</p>
    </div>

    <div class="mb-6 flex flex-wrap items-end gap-4">
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">From</label>
            <input type="date" wire:model.live="dateFrom" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">To</label>
            <input type="date" wire:model.live="dateTo" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-sm text-slate-500">Revenue</p>
            <p class="mt-1 text-lg font-semibold text-slate-900">KES {{ number_format($revenue_cents / 100, 2) }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-sm text-slate-500">Cost of goods sold</p>
            <p class="mt-1 text-lg font-semibold text-slate-900">KES {{ number_format($cogs_cents / 100, 2) }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-sm text-slate-500">Profit</p>
            <p class="mt-1 text-lg font-semibold {{ $profit_cents >= 0 ? 'text-emerald-700' : 'text-red-600' }}">
                KES {{ number_format($profit_cents / 100, 2) }}
            </p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-sm text-slate-500">Margin</p>
            <p class="mt-1 text-lg font-semibold text-slate-900">{{ $margin_percent }}%</p>
        </div>
    </div>
</div>
