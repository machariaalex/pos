<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-slate-900">Customers</h1>
        <button wire:click="startCreate" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
            New customer
        </button>
    </div>

    <input
        type="text"
        wire:model.live.debounce.300ms="search"
        placeholder="Search by name or phone..."
        class="mb-4 w-72 rounded-md border border-slate-300 px-3 py-2 text-sm"
    >

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-2">Name</th>
                    <th class="px-4 py-2">Phone</th>
                    <th class="px-4 py-2">Balance</th>
                    <th class="px-4 py-2">Credit limit</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($customers as $customer)
                    <tr>
                        <td class="px-4 py-2">
                            <a href="{{ route('customers.show', $customer) }}" class="font-medium text-emerald-700 hover:underline">
                                {{ $customer->name }}
                            </a>
                        </td>
                        <td class="px-4 py-2 text-slate-600">{{ $customer->phone }}</td>
                        <td class="px-4 py-2">
                            <span class="{{ $customer->balance_cents > 0 ? 'text-red-600 font-medium' : 'text-slate-600' }}">
                                KES {{ number_format($customer->balance_cents / 100, 2) }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-slate-500">
                            {{ $customer->hasCreditLimit() ? 'KES '.number_format($customer->credit_limit_cents / 100, 2) : 'No limit' }}
                        </td>
                        <td class="px-4 py-2 text-right">
                            <button wire:click="startEdit({{ $customer->id }})" class="text-emerald-700 hover:underline">Edit</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-400">No customers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $customers->links() }}
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-10 flex items-center justify-center bg-slate-900/40 px-4" wire:click.self="$set('showForm', false)">
            <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-lg">
                <h2 class="mb-4 text-lg font-semibold">{{ $editingCustomerId ? 'Edit customer' : 'New customer' }}</h2>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Name</label>
                        <input type="text" wire:model="name" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Phone</label>
                        <input type="text" wire:model="phone" placeholder="07XXXXXXXX" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    @if ($canManageCreditLimit)
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" wire:model.live="hasCreditLimit" class="rounded border-slate-300">
                            Set a credit limit
                        </label>
                        @if ($hasCreditLimit)
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Credit limit (KES)</label>
                                <input type="number" step="0.01" wire:model="creditLimit" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                                @error('creditLimit') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        @endif
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
