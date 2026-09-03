@props([
    'icon' => 'banknotes',
    'label' => '',
    'value' => '',
    'delta' => null,
    'deltaLabel' => 'from yesterday',
    'sparkline' => [],
])

@php
    $deltaUp = is_numeric($delta) && $delta > 0;
    $deltaDown = is_numeric($delta) && $delta < 0;
@endphp

<div {{ $attributes->class(['relative overflow-hidden rounded-card bg-linear-to-br from-primary-700 via-primary-800 to-primary-950 p-5 text-white shadow-lg shadow-primary-950/25']) }}>
    <div class="pointer-events-none absolute -right-8 -top-12 h-40 w-40 rounded-full bg-primary-400/20 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-10 -left-6 h-28 w-28 rounded-full bg-primary-300/10 blur-2xl"></div>

    <div class="relative flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-primary-200">
                <x-dynamic-component :component="'heroicon-o-'.$icon" class="h-3.5 w-3.5 shrink-0" />
                <span class="truncate">{{ $label }}</span>
            </p>
            <p class="font-tabular mt-1.5 truncate text-[1.75rem] font-bold leading-none sm:text-3xl">{{ $value }}</p>
        </div>

        @if ($delta !== null)
            <span @class([
                'flex shrink-0 items-center gap-0.5 rounded-full px-2 py-1 text-xs font-semibold',
                'bg-white/15' => $deltaUp,
                'bg-black/20' => $deltaDown,
                'bg-white/10 text-primary-200' => ! $deltaUp && ! $deltaDown,
            ])>
                @if ($deltaUp)
                    <x-heroicon-m-arrow-trending-up class="h-3.5 w-3.5" />
                @elseif ($deltaDown)
                    <x-heroicon-m-arrow-trending-down class="h-3.5 w-3.5" />
                @endif
                {{ number_format(abs($delta), 1) }}%
            </span>
        @endif
    </div>

    @if (count($sparkline) > 1)
        <div class="relative mt-4 h-10">
            <x-sparkline :values="$sparkline" stroke="#ffffff" fill="#ffffff" :height="40" :width="280" />
        </div>
    @endif

    @if ($delta !== null)
        <p class="relative mt-2 text-xs text-primary-200">{{ $deltaLabel }}</p>
    @endif
</div>
