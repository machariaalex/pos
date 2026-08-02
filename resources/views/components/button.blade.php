@props(['variant' => 'secondary', 'size' => 'md', 'href' => null])

@php
    $variants = [
        'primary' => 'bg-primary-700 text-white hover:bg-primary-800 focus-visible:outline-primary-700',
        'secondary' => 'bg-white text-text-primary border border-surface-border hover:bg-surface-muted focus-visible:outline-primary-700',
        'danger' => 'bg-danger-600 text-white hover:bg-danger-700 focus-visible:outline-danger-600',
        'ghost' => 'text-text-secondary hover:bg-surface-muted focus-visible:outline-primary-700',
    ];

    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs gap-1.5',
        'md' => 'px-4 py-2.5 text-sm gap-2',
        'lg' => 'px-5 py-3.5 text-base gap-2.5',
    ];

    $classes = 'inline-flex items-center justify-center rounded-lg font-medium transition-colors '
        .'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 '
        .'disabled:cursor-not-allowed disabled:opacity-40 '
        .($variants[$variant] ?? $variants['secondary']).' '
        .($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class([$classes]) }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->class([$classes]) }}>
        {{ $slot }}
    </button>
@endif
