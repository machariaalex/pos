<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-text-primary">Users</h1>
        <x-button variant="primary" wire:click="startCreate">
            <x-heroicon-o-plus class="h-4 w-4" />
            New user
        </x-button>
    </div>

    @error('form') <p class="mb-4 text-sm text-danger-600">{{ $message }}</p> @enderror

    <x-table>
        <thead>
            <tr>
                <x-table.th>Name</x-table.th>
                <x-table.th>Email</x-table.th>
                <x-table.th>Role</x-table.th>
                <x-table.th>Status</x-table.th>
                <x-table.th></x-table.th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr class="hover:bg-surface-muted/50">
                    <x-table.td>
                        <span class="font-medium text-text-primary">{{ $user->name }}</span>
                    </x-table.td>
                    <x-table.td>
                        <span class="text-text-secondary">{{ $user->email }}</span>
                    </x-table.td>
                    <x-table.td>
                        <span class="capitalize text-text-secondary">{{ $user->role }}</span>
                    </x-table.td>
                    <x-table.td>
                        <x-badge :variant="$user->is_active ? 'success' : 'neutral'">
                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                        </x-badge>
                    </x-table.td>
                    <x-table.td align="right">
                        <div class="flex items-center justify-end gap-3">
                            <x-button variant="ghost" size="sm" wire:click="startEdit({{ $user->id }})">Edit</x-button>
                            <x-button
                                variant="ghost"
                                size="sm"
                                wire:click="toggleActive({{ $user->id }})"
                                wire:confirm="Are you sure?"
                            >
                                {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                            </x-button>
                        </div>
                    </x-table.td>
                </tr>
            @endforeach
        </tbody>
    </x-table>

    @if ($showForm)
        <x-modal :title="$editingUserId ? 'Edit user' : 'New user'" wire:click.self="$set('showForm', false)">
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-text-primary">Name</label>
                    <input type="text" wire:model="name" class="w-full rounded-lg border border-surface-border bg-surface-card px-3 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary-700/30">
                    @error('name') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-text-primary">Email</label>
                    <input type="email" wire:model="email" class="w-full rounded-lg border border-surface-border bg-surface-card px-3 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary-700/30">
                    @error('email') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-text-primary">Phone</label>
                    <input type="text" wire:model="phone" placeholder="07XXXXXXXX" class="w-full rounded-lg border border-surface-border bg-surface-card px-3 py-2 text-sm text-text-primary placeholder:text-text-muted focus:outline-none focus:ring-2 focus:ring-primary-700/30">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-text-primary">Role</label>
                    <select wire:model="role" class="w-full rounded-lg border border-surface-border bg-surface-card px-3 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary-700/30">
                        <option value="owner">Owner</option>
                        <option value="manager">Manager</option>
                        <option value="attendant">Attendant</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-text-primary">
                        Password {{ $editingUserId ? '(leave blank to keep current)' : '' }}
                    </label>
                    <input type="password" wire:model="password" class="w-full rounded-lg border border-surface-border bg-surface-card px-3 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary-700/30">
                    @error('password') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-text-primary">
                        4-digit approval PIN {{ $editingUserId ? '(leave blank to keep current)' : '(owner/manager only)' }}
                    </label>
                    <input type="password" inputmode="numeric" maxlength="4" wire:model="pin" class="w-full rounded-lg border border-surface-border bg-surface-card px-3 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary-700/30">
                    @error('pin') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                </div>

                @if ($role !== 'owner')
                    <div>
                        <label class="mb-1 block text-sm font-medium text-text-primary">Extra permissions</label>
                        <p class="mb-2 text-xs text-text-muted">On top of what their role normally allows.</p>
                        <div class="space-y-1.5 rounded-lg border border-surface-border p-3">
                            @foreach (\App\Models\User::PERMISSIONS as $key => $label)
                                <label class="flex items-center gap-2 text-sm text-text-secondary">
                                    <input type="checkbox" wire:model="permissions" value="{{ $key }}" class="rounded border-surface-border text-primary-700 focus:ring-primary-700/30">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="flex justify-end gap-3 pt-2">
                    <x-button variant="ghost" type="button" wire:click="$set('showForm', false)">Cancel</x-button>
                    <x-button variant="primary" type="submit">Save</x-button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
