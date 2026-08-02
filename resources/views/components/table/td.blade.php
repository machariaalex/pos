@props(['align' => 'left'])

<td {{ $attributes->class([
        'border-t border-surface-border px-4 py-3 text-text-primary',
        'text-left' => $align === 'left',
        'text-right' => $align === 'right',
        'text-center' => $align === 'center',
]) }}>
    {{ $slot }}
</td>
