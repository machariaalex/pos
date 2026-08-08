@props(['model'])

<div x-data="{ show: false }" class="relative">
    <input
        :type="show ? 'text' : 'password'"
        wire:model="{{ $model }}"
        {{ $attributes->class(['w-full rounded-lg border border-surface-border bg-surface-card px-3 py-2 pr-10 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary-700/30']) }}
    >
    <button
        type="button"
        @click="show = !show"
        tabindex="-1"
        class="absolute inset-y-0 right-0 flex items-center px-3 text-text-muted hover:text-text-primary"
    >
        <x-heroicon-o-eye x-show="!show" class="h-4 w-4" />
        <x-heroicon-o-eye-slash x-show="show" x-cloak class="h-4 w-4" />
        <span class="sr-only" x-text="show ? 'Hide password' : 'Show password'"></span>
    </button>
</div>
