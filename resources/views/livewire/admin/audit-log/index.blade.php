<div>
    <h1 class="mb-6 text-2xl font-semibold text-slate-900">Audit log</h1>

    <div class="mb-4 flex flex-wrap items-end gap-4">
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Action</label>
            <select wire:model.live="action" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">All actions</option>
                @foreach ($actions as $a)
                    <option value="{{ $a }}">{{ $a }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">User</label>
            <select wire:model.live="userId" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">All users</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">From</label>
            <input type="date" wire:model.live="dateFrom" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">To</label>
            <input type="date" wire:model.live="dateTo" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-2">Time</th>
                    <th class="px-4 py-2">User</th>
                    <th class="px-4 py-2">Action</th>
                    <th class="px-4 py-2">Description</th>
                    <th class="px-4 py-2">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($logs as $log)
                    <tr>
                        <td class="px-4 py-2 whitespace-nowrap text-slate-500">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                        <td class="px-4 py-2 text-slate-600">{{ $log->user?->name ?? 'System' }}</td>
                        <td class="px-4 py-2"><span class="rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-600">{{ $log->action }}</span></td>
                        <td class="px-4 py-2 text-slate-600">{{ $log->description }}</td>
                        <td class="px-4 py-2 text-slate-400">{{ $log->ip_address }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-400">No matching entries.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $logs->links() }}
    </div>
</div>
