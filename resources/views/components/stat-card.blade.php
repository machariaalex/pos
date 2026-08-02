@props([
    'icon' => 'chart-bar',
    'variant' => 'primary',
    'label' => '',
    'value' => '',
    'delta' => null,
    'deltaLabel' => 'from yesterday',
])

@php
    $tint = match ($variant) {
        'warn' => ['bg' => 'bg-warn-100', 'text' => 'text-warn-600'],
        'danger' => ['bg' => 'bg-danger-100', 'text' => 'text-danger-600'],
        'info' => ['bg' => 'bg-info-100', 'text' => 'text-info-600'],
        'success' => ['bg' => 'bg-success-100', 'text' => 'text-success-600'],
        default => ['bg' => 'bg-primary-100', 'text' => 'text-primary-700'],
    };

    $deltaUp = is_numeric($delta) && $delta > 0;
    $deltaDown = is_numeric($delta) && $delta < 0;
@endphp

<div {{ $attributes->class(['bg-surface-card rounded-card border border-surface-border p-5 shadow-sm']) }}>
    <div class="flex items-center gap-3">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $tint['bg'] }} {{ $tint['text'] }}">
            <x-dynamic-component :component="'heroicon-o-'.$icon" class="h-5 w-5" />
        </div>
        <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-text-muted">{{ $label }}</p>
            <p class="font-tabular truncate text-xl font-semibold text-text-primary">{{ $value }}</p>
        </div>
    </div>

    @if ($delta !== null)
        <p class="mt-3 flex items-center gap-1 text-xs">
            @if ($deltaUp)
                <x-heroicon-m-arrow-trending-up class="h-3.5 w-3.5 text-success-600" />
                <span class="font-medium text-success-600">{{ number_format(abs($delta), 1) }}%</span>
            @elseif ($deltaDown)
                <x-heroicon-m-arrow-trending-down class="h-3.5 w-3.5 text-danger-600" />
                <span class="font-medium text-danger-600">{{ number_format(abs($delta), 1) }}%</span>
            @else
                <span class="font-medium text-text-muted">No change</span>
            @endif
            <span class="text-text-muted">{{ $deltaLabel }}</span>
        </p>
    @endif
</div>
