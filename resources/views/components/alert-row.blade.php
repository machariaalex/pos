@props(['variant' => 'warn', 'icon' => 'exclamation-triangle'])

@php
    $tint = match ($variant) {
        'danger' => ['bg' => 'bg-danger-50', 'iconBg' => 'bg-danger-100', 'iconText' => 'text-danger-600'],
        'success' => ['bg' => 'bg-success-50', 'iconBg' => 'bg-success-100', 'iconText' => 'text-success-600'],
        'info' => ['bg' => 'bg-info-50', 'iconBg' => 'bg-info-100', 'iconText' => 'text-info-600'],
        default => ['bg' => 'bg-warn-50', 'iconBg' => 'bg-warn-100', 'iconText' => 'text-warn-600'],
    };
@endphp

<div {{ $attributes->class(["flex items-center gap-3 rounded-lg {$tint['bg']} px-3 py-2.5"]) }}>
    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $tint['iconBg'] }} {{ $tint['iconText'] }}">
        <x-dynamic-component :component="'heroicon-o-'.$icon" class="h-4 w-4" />
    </div>
    <div class="min-w-0 flex-1">
        {{ $slot }}
    </div>
    @isset($action)
        <div class="shrink-0">{{ $action }}</div>
    @endisset
</div>
