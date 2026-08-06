<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-text-primary">Customers</h1>
        <x-button variant="primary" wire:click="startCreate">
            <x-heroicon-o-plus class="h-4 w-4" />
            New customer
        </x-button>
    </div>

    <div class="mb-4">
        <div class="relative w-72">
            <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-text-muted" />
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search by name or phone..."
                class="w-full rounded-lg border border-surface-border bg-surface-card py-2 pl-9 pr-3 text-sm text-text-primary placeholder:text-text-muted focus:outline-none focus:ring-2 focus:ring-primary-700/30"
            >
        </div>
    </div>

    <x-table scrollable>
        <thead>
            <tr>
                <x-table.th>Name</x-table.th>
                <x-table.th>Phone</x-table.th>
                <x-table.th>Balance</x-table.th>
                <x-table.th>Credit limit</x-table.th>
                <x-table.th></x-table.th>
            </tr>
        </thead>
        <tbody>
            @forelse ($customers as $customer)
                <tr class="hover:bg-surface-muted/50">
                    <x-table.td>
                        <a href="{{ route('customers.show', $customer) }}" class="font-medium text-primary-700 hover:underline">
                            {{ $customer->name }}
                        </a>
                    </x-table.td>
                    <x-table.td>
                        <span class="text-text-secondary">{{ $customer->phone }}</span>
                    </x-table.td>
                    <x-table.td>
                        <span class="font-tabular {{ $customer->balance_cents > 0 ? 'font-semibold text-danger-600' : 'text-text-secondary' }}">
                            KES {{ number_format($customer->balance_cents / 100, 2) }}
                        </span>
                    </x-table.td>
                    <x-table.td>
                        <span class="font-tabular text-text-secondary">
                            {{ $customer->hasCreditLimit() ? 'KES '.number_format($customer->credit_limit_cents / 100, 2) : 'No limit' }}
                        </span>
                    </x-table.td>
                    <x-table.td align="right">
                        <x-button variant="ghost" size="sm" wire:click="startEdit({{ $customer->id }})">
                            Edit
                        </x-button>
                    </x-table.td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        <x-empty-state icon="users" title="No customers found" description="Add your first customer or adjust your search." />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>

    <div class="mt-4">
        {{ $customers->links() }}
    </div>

    @if ($showForm)
        <x-modal :title="$editingCustomerId ? 'Edit customer' : 'New customer'" wire:click.self="$set('showForm', false)">
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-text-primary">Name</label>
                    <input type="text" wire:model="name" class="w-full rounded-lg border border-surface-border bg-surface-card px-3 py-2 text-sm text-text-primary placeholder:text-text-muted focus:outline-none focus:ring-2 focus:ring-primary-700/30">
                    @error('name') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-text-primary">Phone</label>
                    <input type="text" wire:model="phone" placeholder="07XXXXXXXX" class="w-full rounded-lg border border-surface-border bg-surface-card px-3 py-2 text-sm text-text-primary placeholder:text-text-muted focus:outline-none focus:ring-2 focus:ring-primary-700/30">
                    @error('phone') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                </div>

                @if ($canManageCreditLimit)
                    <label class="flex items-center gap-2 text-sm text-text-secondary">
                        <input type="checkbox" wire:model.live="hasCreditLimit" class="rounded border-surface-border text-primary-700 focus:ring-primary-700/30">
                        Set a credit limit
                    </label>
                    @if ($hasCreditLimit)
                        <div>
                            <label class="mb-1 block text-sm font-medium text-text-primary">Credit limit (KES)</label>
                            <input type="number" step="0.01" wire:model="creditLimit" class="w-full rounded-lg border border-surface-border bg-surface-card px-3 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary-700/30">
                            @error('creditLimit') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                        </div>
                    @endif
                @endif

                <div class="flex justify-end gap-3 pt-2">
                    <x-button variant="ghost" type="button" wire:click="$set('showForm', false)">Cancel</x-button>
                    <x-button variant="primary" type="submit">Save</x-button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
