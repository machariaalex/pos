@props(['route', 'active', 'icon'])

<a
    href="{{ $route }}"
    title="{{ $slot }}"
    @class([
        'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
        'bg-primary-800 text-white' => $active,
        'text-primary-100 hover:bg-primary-800/60 hover:text-white' => ! $active,
    ])
>
    <x-dynamic-component :component="'heroicon-o-'.$icon" class="h-5 w-5 shrink-0" />
    <span class="truncate">{{ $slot }}</span>
</a>
