<div class="w-full max-w-sm rounded-card border border-surface-border bg-surface-card p-8 shadow-sm">
    <div class="mb-6">
        <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-primary-700 text-white">
            <x-heroicon-o-shopping-bag class="h-6 w-6" />
        </div>
        <h1 class="text-xl font-semibold text-text-primary">{{ config('app.name') }}</h1>
        <p class="mt-1 text-sm text-text-muted">Sign in to continue</p>
    </div>

    <form wire:submit="login" class="space-y-4">
        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-text-primary">Email</label>
            <input
                type="email"
                id="email"
                wire:model="email"
                autofocus
                autocomplete="username"
                class="w-full rounded-lg border border-surface-border bg-surface-card px-3 py-2 text-sm text-text-primary placeholder:text-text-muted focus:outline-none focus:ring-2 focus:ring-primary-700/30"
            >
            @error('email') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-medium text-text-primary">Password</label>
            <input
                type="password"
                id="password"
                wire:model="password"
                autocomplete="current-password"
                class="w-full rounded-lg border border-surface-border bg-surface-card px-3 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary-700/30"
            >
            @error('password') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-text-secondary">
            <input type="checkbox" wire:model="remember" class="rounded border-surface-border text-primary-700 focus:ring-primary-700/30">
            Remember me
        </label>

        <x-button variant="primary" type="submit" class="w-full justify-center" wire:loading.attr="disabled">
            Sign in
        </x-button>
    </form>
</div>
