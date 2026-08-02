@props(['icon' => 'inbox', 'title' => 'Nothing here yet', 'description' => null])

<div {{ $attributes->class(['flex flex-col items-center justify-center px-6 py-12 text-center']) }}>
    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-surface-muted text-text-muted">
        <x-dynamic-component :component="'heroicon-o-'.$icon" class="h-6 w-6" />
    </div>
    <p class="text-sm font-medium text-text-primary">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 max-w-xs text-sm text-text-muted">{{ $description }}</p>
    @endif
    @isset($action)
        <div class="mt-4">{{ $action }}</div>
    @endisset
</div>
