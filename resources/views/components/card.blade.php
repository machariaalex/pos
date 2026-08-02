@props(['title' => null, 'subtitle' => null, 'padding' => true])

<div {{ $attributes->class(['bg-surface-card rounded-card border border-surface-border shadow-sm']) }}>
    @if ($title || isset($actions))
        <div class="flex items-start justify-between gap-4 border-b border-surface-border px-5 py-4">
            <div>
                @if ($title)
                    <h2 class="text-sm font-semibold text-text-primary">{{ $title }}</h2>
                @endif
                @if ($subtitle)
                    <p class="mt-0.5 text-xs text-text-muted">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div @class(['p-5' => $padding])>
        {{ $slot }}
    </div>
</div>
