@props(['title' => null, 'maxWidth' => 'md'])

@php
    $widths = ['sm' => 'max-w-sm', 'md' => 'max-w-md', 'lg' => 'max-w-lg', 'xl' => 'max-w-xl'];
@endphp

<div {{ $attributes->class(['fixed inset-0 z-30 flex items-center justify-center bg-primary-950/40 px-4 print:hidden']) }}>
    <div class="w-full {{ $widths[$maxWidth] ?? $widths['md'] }} rounded-card bg-surface-card p-6 shadow-xl">
        @if ($title)
            <h2 class="mb-4 text-lg font-semibold text-text-primary">{{ $title }}</h2>
        @endif

        {{ $slot }}

        @isset($footer)
            <div class="mt-5 flex justify-end gap-3">{{ $footer }}</div>
        @endisset
    </div>
</div>
