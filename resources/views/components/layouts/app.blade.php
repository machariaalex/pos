<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex min-h-screen bg-surface text-text-primary antialiased">
    <aside class="flex w-16 shrink-0 flex-col bg-primary-950 print:hidden lg:w-64">
        <div class="flex items-center gap-3 px-3 py-5 lg:px-5">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-700 text-white">
                <x-heroicon-o-sparkles class="h-5 w-5" />
            </div>
            <span class="hidden truncate text-base font-semibold text-white lg:inline">{{ config('app.name') }}</span>
        </div>

        <nav class="flex flex-1 flex-col gap-1 overflow-y-auto px-2 py-2 lg:px-3">
            <x-nav-item :route="route('sales.pos')" :active="request()->routeIs('sales.pos')" icon="shopping-cart">Sell</x-nav-item>
            <x-nav-item :route="route('dashboard')" :active="request()->routeIs('dashboard')" icon="home">Dashboard</x-nav-item>
            <x-nav-item :route="route('inventory.products.index')" :active="request()->routeIs('inventory.products.*')" icon="cube">Products</x-nav-item>
            @can('adjust-stock')
                <x-nav-item :route="route('inventory.receive-stock')" :active="request()->routeIs('inventory.receive-stock')" icon="archive-box-arrow-down">Receive Stock</x-nav-item>
                <x-nav-item :route="route('inventory.stock-takes.index')" :active="request()->routeIs('inventory.stock-takes.*')" icon="clipboard-document-check">Stock Takes</x-nav-item>
            @endcan
            <x-nav-item :route="route('customers.index')" :active="request()->routeIs('customers.*')" icon="users">Customers</x-nav-item>
            @can('view-reports')
                <x-nav-item :route="route('reports.index')" :active="request()->routeIs('reports.*')" icon="chart-bar">Reports</x-nav-item>
            @endcan
            <x-nav-item :route="route('cash-up.index')" :active="request()->routeIs('cash-up.*')" icon="banknotes">Cash-up</x-nav-item>
            @can('view-audit-log')
                <x-nav-item :route="route('audit-log.index')" :active="request()->routeIs('audit-log.*')" icon="shield-check">Audit Log</x-nav-item>
            @endcan
            @can('manage-users')
                <x-nav-item :route="route('users.index')" :active="request()->routeIs('users.*')" icon="user-group">Users</x-nav-item>
            @endcan
        </nav>

        <div class="border-t border-primary-800 p-3 lg:p-4">
            <div class="mb-3 hidden items-center gap-2 lg:flex">
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                    <span class="mt-0.5 inline-flex items-center rounded-full bg-primary-800 px-2.5 py-0.5 text-xs font-medium capitalize text-primary-100">
                        {{ auth()->user()->role }}
                    </span>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" title="Log out" class="flex w-full items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-primary-100 transition-colors hover:bg-primary-800/60 hover:text-white lg:justify-start">
                    <x-heroicon-o-arrow-left-start-on-rectangle class="h-5 w-5 shrink-0" />
                    <span class="hidden lg:inline">Log out</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="min-w-0 flex-1">
        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 print:max-w-none print:p-0">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
