@props([
    'label' => '',
    'value' => '',
    'variant' => 'default',
    'delta' => null,
])

@php
    $valueColor = match ($variant) {
        'warn' => 'text-warn-700',
        'danger' => 'text-danger-700',
        'success' => 'text-success-700',
        default => 'text-text-primary',
    };
    $deltaUp = is_numeric($delta) && $delta > 0;
    $deltaDown = is_numeric($delta) && $delta < 0;
@endphp

<div {{ $attributes->class(['rounded-card border border-surface-border bg-surface-card p-3.5 shadow-sm sm:p-4']) }}>
    <p class="truncate text-[0.6875rem] font-medium uppercase tracking-wide text-text-muted sm:text-xs">{{ $label }}</p>
    <p class="font-tabular mt-1 truncate text-lg font-semibold {{ $valueColor }} sm:text-xl">{{ $value }}</p>
    @if ($delta !== null)
        <p class="mt-1 text-xs">
            @if ($deltaUp)
                <span class="font-medium text-success-600">&uarr; {{ number_format(abs($delta), 1) }}%</span>
            @elseif ($deltaDown)
                <span class="font-medium text-danger-600">&darr; {{ number_format(abs($delta), 1) }}%</span>
            @else
                <span class="text-text-muted">No change</span>
            @endif
        </p>
    @endif
</div>
