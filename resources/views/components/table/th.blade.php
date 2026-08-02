@props(['align' => 'left'])

<th {{ $attributes->class([
        'sticky top-0 z-10 bg-surface-muted px-4 py-2.5 text-xs font-semibold uppercase tracking-wide text-text-secondary',
        'text-left' => $align === 'left',
        'text-right' => $align === 'right',
        'text-center' => $align === 'center',
]) }}>
    {{ $slot }}
</th>
