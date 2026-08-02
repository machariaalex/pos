<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-slate-900">Users</h1>
        <button wire:click="startCreate" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
            New user
        </button>
    </div>

    @error('form') <p class="mb-4 text-sm text-red-600">{{ $message }}</p> @enderror

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-2">Name</th>
                    <th class="px-4 py-2">Email</th>
                    <th class="px-4 py-2">Role</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($users as $user)
                    <tr>
                        <td class="px-4 py-2">{{ $user->name }}</td>
                        <td class="px-4 py-2">{{ $user->email }}</td>
                        <td class="px-4 py-2 capitalize">{{ $user->role }}</td>
                        <td class="px-4 py-2">
                            <span class="rounded px-2 py-0.5 text-xs {{ $user->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right">
                            <button wire:click="startEdit({{ $user->id }})" class="text-emerald-700 hover:underline">Edit</button>
                            <button
                                wire:click="toggleActive({{ $user->id }})"
                                wire:confirm="Are you sure?"
                                class="ml-3 text-slate-500 hover:underline"
                            >
                                {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-10 flex items-center justify-center bg-slate-900/40 px-4" wire:click.self="$set('showForm', false)">
            <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-lg">
                <h2 class="mb-4 text-lg font-semibold">{{ $editingUserId ? 'Edit user' : 'New user' }}</h2>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Name</label>
                        <input type="text" wire:model="name" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                        <input type="email" wire:model="email" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Phone</label>
                        <input type="text" wire:model="phone" placeholder="07XXXXXXXX" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Role</label>
                        <select wire:model="role" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                            <option value="owner">Owner</option>
                            <option value="manager">Manager</option>
                            <option value="attendant">Attendant</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Password {{ $editingUserId ? '(leave blank to keep current)' : '' }}
                        </label>
                        <input type="password" wire:model="password" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            4-digit approval PIN {{ $editingUserId ? '(leave blank to keep current)' : '(owner/manager only)' }}
                        </label>
                        <input type="password" inputmode="numeric" maxlength="4" wire:model="pin" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        @error('pin') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    @if ($role !== 'owner')
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Extra permissions</label>
                            <p class="mb-2 text-xs text-slate-500">On top of what their role normally allows.</p>
                            <div class="space-y-1.5 rounded-md border border-slate-200 p-3">
                                @foreach (\App\Models\User::PERMISSIONS as $key => $label)
                                    <label class="flex items-center gap-2 text-sm text-slate-600">
                                        <input type="checkbox" wire:model="permissions" value="{{ $key }}" class="rounded border-slate-300">
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="$set('showForm', false)" class="rounded-md px-4 py-2 text-sm text-slate-600 hover:bg-slate-100">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
