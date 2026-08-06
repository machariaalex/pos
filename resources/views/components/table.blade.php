@props(['empty' => false, 'scrollable' => false])

<div {{ $attributes->class(['overflow-hidden rounded-card border border-surface-border bg-surface-card shadow-sm']) }}>
    <div @class(['overflow-x-auto', 'overflow-y-auto' => $scrollable]) @style($scrollable ? 'min-height: 450px; max-height: calc(100vh - 160px)' : null)>
        <table class="w-full text-sm">
            {{ $slot }}
        </table>
    </div>
</div>
