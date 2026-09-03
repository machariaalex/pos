<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/waingo.png') }}">

    {{-- Installable app (PWA): home-screen icon + standalone window. Does
         not enable offline sales/inventory — see public/sw.js. --}}
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <meta name="theme-color" content="#0b1f12">
    <link rel="apple-touch-icon" href="{{ asset('images/icons/apple-touch-icon.png') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Waingo Farm">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex h-screen overflow-hidden bg-surface text-text-primary antialiased" x-data="{ sidebarOpen: false }">
    <div
        x-show="sidebarOpen"
        x-cloak
        x-transition.opacity
        @click="sidebarOpen = false"
        class="fixed inset-0 z-30 bg-black/50 lg:hidden"
    ></div>

    <aside
        class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-primary-950 transition-transform duration-200 ease-in-out print:hidden lg:static lg:z-auto lg:translate-none"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    >
        <div class="flex items-center gap-3 px-4 py-4 lg:px-5">
            <a href="{{ route('dashboard') }}" class="flex min-w-0 flex-1 items-center gap-3">
                <img src="{{ asset('images/waingo.png') }}" alt="Waingo Farm Agrovet" class="h-10 w-10 shrink-0 rounded-full object-cover ring-2 ring-primary-700/50">
                <span class="truncate text-base font-semibold text-white">Waingo Farm</span>
            </a>
            <button type="button" @click="sidebarOpen = false" class="shrink-0 text-primary-200 hover:text-white lg:hidden">
                <x-heroicon-o-x-mark class="h-6 w-6" />
            </button>
        </div>

        <nav class="flex flex-1 flex-col gap-1 overflow-y-auto px-3 py-2">
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
            @can('manage-expenses')
                <x-nav-item :route="route('expenses.index')" :active="request()->routeIs('expenses.*')" icon="arrow-trending-down">Expenses</x-nav-item>
            @endcan
            <x-nav-item :route="route('cash-up.index')" :active="request()->routeIs('cash-up.*')" icon="banknotes">Cash-up</x-nav-item>
            @can('view-audit-log')
                <x-nav-item :route="route('audit-log.index')" :active="request()->routeIs('audit-log.*')" icon="shield-check">Audit Log</x-nav-item>
            @endcan
            @can('manage-users')
                <x-nav-item :route="route('users.index')" :active="request()->routeIs('users.*')" icon="user-group">Users</x-nav-item>
            @endcan
        </nav>

        <div class="border-t border-primary-800 p-4">
            <div class="mb-3 flex items-center gap-2">
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                    <span class="mt-0.5 inline-flex items-center rounded-full bg-primary-800 px-2.5 py-0.5 text-xs font-medium capitalize text-primary-100">
                        {{ auth()->user()->role }}
                    </span>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" title="Log out" class="flex w-full items-center justify-start gap-2 rounded-lg px-3 py-2 text-sm font-medium text-primary-100 transition-colors hover:bg-primary-800/60 hover:text-white">
                    <x-heroicon-o-arrow-left-start-on-rectangle class="h-5 w-5 shrink-0" />
                    <span>Log out</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="min-h-0 min-w-0 flex-1 overflow-y-auto">
        <div class="sticky top-0 z-20 flex items-center gap-3 border-b border-surface-border bg-surface-card px-4 py-3 lg:hidden print:hidden">
            <button type="button" @click="sidebarOpen = true" class="flex h-9 w-9 items-center justify-center rounded-lg text-text-secondary hover:bg-surface-muted">
                <x-heroicon-o-bars-3 class="h-6 w-6" />
            </button>
            <img src="{{ asset('images/waingo.png') }}" alt="" class="h-7 w-7 rounded-full object-cover">
            <span class="truncate text-sm font-semibold text-text-primary">Waingo Farm</span>
        </div>

        <main class="mx-auto max-w-7xl px-4 pb-24 pt-6 sm:px-6 lg:px-8 lg:pb-6 print:max-w-none print:p-0">
            {{ $slot }}
        </main>
    </div>

    {{-- Mobile bottom tab bar: the app's most-reached-for destinations one
         tap away, without opening the drawer. "More" opens the same
         drawer as the top bar's hamburger for everything else. --}}
    <nav
        class="fixed inset-x-0 bottom-0 z-20 flex items-stretch border-t border-surface-border bg-surface-card/95 backdrop-blur-sm lg:hidden print:hidden"
        style="padding-bottom: env(safe-area-inset-bottom)"
    >
        <a href="{{ route('dashboard') }}" @class(['flex flex-1 flex-col items-center justify-center gap-0.5 py-2.5 text-xs font-medium transition-colors', 'text-primary-700' => request()->routeIs('dashboard'), 'text-text-muted' => ! request()->routeIs('dashboard')])>
            <x-heroicon-o-home class="h-5 w-5" />
            Dashboard
        </a>

        <a href="{{ route('sales.pos') }}" class="flex flex-1 flex-col items-center justify-center gap-1 py-1.5">
            <span @class(['flex h-11 w-11 items-center justify-center rounded-full shadow-md shadow-primary-950/30 transition-transform active:scale-95', 'bg-primary-600' => request()->routeIs('sales.pos'), 'bg-primary-800' => ! request()->routeIs('sales.pos')])>
                <x-heroicon-o-shopping-cart class="h-5 w-5 text-white" />
            </span>
            <span @class(['text-xs font-medium transition-colors', 'text-primary-700' => request()->routeIs('sales.pos'), 'text-text-muted' => ! request()->routeIs('sales.pos')])>Sell</span>
        </a>

        <a href="{{ route('inventory.products.index') }}" @class(['flex flex-1 flex-col items-center justify-center gap-0.5 py-2.5 text-xs font-medium transition-colors', 'text-primary-700' => request()->routeIs('inventory.products.*'), 'text-text-muted' => ! request()->routeIs('inventory.products.*')])>
            <x-heroicon-o-cube class="h-5 w-5" />
            Products
        </a>

        <button type="button" @click="sidebarOpen = true" class="flex flex-1 flex-col items-center justify-center gap-0.5 py-2.5 text-xs font-medium text-text-muted transition-colors">
            <x-heroicon-o-bars-3 class="h-5 w-5" />
            More
        </button>
    </nav>

    @livewireScripts
</body>
</html>
