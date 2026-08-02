@props(['variant' => 'neutral'])

@php
    $classes = match ($variant) {
        'warn' => 'bg-warn-100 text-warn-700',
        'danger' => 'bg-danger-100 text-danger-700',
        'info' => 'bg-info-100 text-info-700',
        'success', 'primary' => 'bg-success-100 text-success-700',
        default => 'bg-surface-muted text-text-secondary',
    };
@endphp

<span {{ $attributes->class(["inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium {$classes}"]) }}>
    {{ $slot }}
</span>
