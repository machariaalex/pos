<div>
    <h1 class="mb-6 text-2xl font-semibold text-text-primary">Audit log</h1>

    <div class="mb-4 flex flex-wrap items-end gap-4">
        <div>
            <label class="mb-1 block text-sm font-medium text-text-primary">Action</label>
            <select wire:model.live="action" class="rounded-lg border border-surface-border bg-surface-card px-3 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary-700/30">
                <option value="">All actions</option>
                @foreach ($actions as $a)
                    <option value="{{ $a }}">{{ $a }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-text-primary">User</label>
            <select wire:model.live="userId" class="rounded-lg border border-surface-border bg-surface-card px-3 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary-700/30">
                <option value="">All users</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-text-primary">From</label>
            <input type="date" wire:model.live="dateFrom" class="rounded-lg border border-surface-border bg-surface-card px-3 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary-700/30">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-text-primary">To</label>
            <input type="date" wire:model.live="dateTo" class="rounded-lg border border-surface-border bg-surface-card px-3 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary-700/30">
        </div>
    </div>

    <x-table>
        <thead>
            <tr>
                <x-table.th>Time</x-table.th>
                <x-table.th>User</x-table.th>
                <x-table.th>Action</x-table.th>
                <x-table.th>Description</x-table.th>
                <x-table.th>IP</x-table.th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                <tr class="hover:bg-surface-muted/50">
                    <x-table.td>
                        <span class="whitespace-nowrap font-tabular text-text-muted">{{ $log->created_at->format('d M Y H:i:s') }}</span>
                    </x-table.td>
                    <x-table.td>
                        <span class="text-text-secondary">{{ $log->user?->name ?? 'System' }}</span>
                    </x-table.td>
                    <x-table.td>
                        <x-badge variant="neutral">{{ $log->action }}</x-badge>
                    </x-table.td>
                    <x-table.td>
                        <span class="text-text-secondary">{{ $log->description }}</span>
                    </x-table.td>
                    <x-table.td>
                        <span class="font-tabular text-text-muted">{{ $log->ip_address }}</span>
                    </x-table.td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        <x-empty-state icon="clipboard-document-list" title="No matching entries" description="Try adjusting the filters." />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>

    <div class="mt-4">
        {{ $logs->links() }}
    </div>
</div>
