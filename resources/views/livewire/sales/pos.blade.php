<div class="flex flex-col gap-6 md:flex-row">
    {{-- Left: search + dropdown results + cart lines --}}
    <div class="min-w-0 flex-1">
        <div class="mb-4 flex items-center gap-3">
            <div class="relative flex-1">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-text-muted">
                    <x-heroicon-o-magnifying-glass class="h-5 w-5" />
                </div>
                <input
                    type="text"
                    wire:model.live.debounce.150ms="search"
                    wire:keydown.enter="addFromSearch"
                    autofocus
                    placeholder="Scan barcode or search product by name..."
                    class="w-full rounded-lg border border-surface-border bg-surface-card py-3.5 pl-11 pr-4 text-base focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600"
                    autocomplete="off"
                >

                @if ($search !== '')
                    <div class="absolute inset-x-0 top-full z-10 mt-1 max-h-80 overflow-y-auto rounded-lg border border-surface-border bg-surface-card shadow-lg">
                        @forelse ($searchResults as $product)
                            <div wire:key="search-result-{{ $product->id }}" class="border-b border-surface-border last:border-0">
                                <button
                                    type="button"
                                    wire:click="addProduct({{ $product->id }})"
                                    class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left hover:bg-primary-50"
                                >
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-text-primary">{{ $product->name }}</p>
                                        <span class="mt-0.5 inline-flex items-center gap-1 text-xs {{ $product->isLowStock() ? 'text-warn-700' : 'text-text-muted' }}">
                                            @if ($product->isLowStock())
                                                <x-heroicon-o-exclamation-triangle class="h-3 w-3" />
                                            @endif
                                            {{ $product->stockOnHand() }} {{ $product->base_unit }} in stock
                                            @if ($product->selling_unit)
                                                &middot; sell per {{ $product->selling_unit }}
                                            @endif
                                        </span>
                                    </div>
                                    <p class="font-tabular shrink-0 text-sm font-semibold text-primary-700">
                                        KES {{ number_format($product->selling_price_cents / 100, 2) }}
                                        <span class="text-xs font-normal text-text-muted">/{{ $product->effectiveSellingUnit() }}</span>
                                    </p>
                                </button>
                                @if ($product->hasBulkPack())
                                    <button
                                        type="button"
                                        wire:click="addProductPack({{ $product->id }})"
                                        class="flex w-full items-center justify-between gap-3 bg-success-50 px-4 py-2 text-left hover:bg-success-100"
                                    >
                                        <span class="text-xs font-medium text-success-700">
                                            + Add 1 bulk pack — {{ number_format($product->pack_size, 0) }} {{ $product->effectiveSellingUnit() }}
                                        </span>
                                        <span class="font-tabular text-xs font-semibold text-success-700">
                                            KES {{ number_format($product->pack_price_cents / 100, 2) }}
                                        </span>
                                    </button>
                                @endif
                            </div>
                        @empty
                            <p class="px-4 py-3 text-sm text-text-muted">No products match.</p>
                        @endforelse
                    </div>
                @endif
            </div>
            <button
                type="button"
                wire:click="$toggle('showHeldSales')"
                class="flex items-center gap-2 rounded-lg border border-surface-border bg-surface-card px-4 py-3.5 text-sm font-medium text-text-primary hover:bg-surface-muted"
            >
                <x-heroicon-o-clock class="h-5 w-5 text-text-muted" />
                Held ({{ $heldSales->count() }})
            </button>
        </div>

        @if ($showHeldSales)
            <div class="mb-4 rounded-card border border-warn-100 bg-warn-50 p-3">
                @forelse ($heldSales as $held)
                    <div class="flex items-center justify-between gap-3 border-b border-warn-100 py-2 text-sm last:border-0">
                        <span class="text-text-primary">
                            {{ $held->sale_number }}
                            @if ($held->customer) &middot; {{ $held->customer->name }} @endif
                            &middot; <span class="font-tabular">KES {{ number_format($held->total_cents / 100, 2) }}</span>
                        </span>
                        <x-button wire:click="resumeSale({{ $held->id }})" variant="secondary" size="sm">Resume</x-button>
                    </div>
                @empty
                    <p class="py-2 text-sm text-warn-700">No held sales.</p>
                @endforelse
            </div>
        @endif

        @error('cart') <p class="mb-4 rounded-lg bg-danger-50 px-3 py-2 text-sm text-danger-700">{{ $message }}</p> @enderror
    </div>

    {{-- Right: fixed cart panel --}}
    <div class="w-full shrink-0 space-y-4 md:w-96 md:sticky md:top-6 md:self-start">
        {{-- Customer --}}
        <x-card :padding="false" class="p-4">
            <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-text-muted">Customer</h2>
            @if ($selectedCustomer)
                <div class="flex items-center justify-between rounded-lg bg-surface-muted px-3 py-2.5 text-sm">
                    <div class="min-w-0">
                        <p class="truncate font-medium text-text-primary">{{ $selectedCustomer->name }}</p>
                        <p class="font-tabular text-xs text-text-muted">
                            Balance KES {{ number_format($selectedCustomer->balance_cents / 100, 2) }}
                            @if ($selectedCustomer->credit_limit_cents !== null)
                                / limit KES {{ number_format($selectedCustomer->credit_limit_cents / 100, 2) }}
                            @endif
                        </p>
                    </div>
                    <button wire:click="clearCustomer" class="shrink-0 p-1.5 text-text-muted hover:text-text-primary">
                        <x-heroicon-o-x-mark class="h-4 w-4" />
                    </button>
                </div>
            @else
                <input
                    type="text"
                    wire:model.live.debounce.200ms="customerSearch"
                    placeholder="Search customer by name or phone..."
                    class="w-full rounded-lg border border-surface-border px-3 py-2.5 text-sm focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600"
                >
                @if ($customerSearch !== '')
                    <div class="mt-2 max-h-40 overflow-y-auto rounded-lg border border-surface-border">
                        @forelse ($customerResults as $customer)
                            <button
                                type="button"
                                wire:click="selectCustomer({{ $customer->id }})"
                                class="block w-full border-b border-surface-border px-3 py-2.5 text-left text-sm last:border-0 hover:bg-surface-muted"
                            >
                                {{ $customer->name }} <span class="text-xs text-text-muted">{{ $customer->phone }}</span>
                            </button>
                        @empty
                            <p class="px-3 py-2.5 text-sm text-text-muted">No matches.</p>
                        @endforelse
                    </div>
                @endif
            @endif
        </x-card>

        {{-- Cart lines --}}
        <x-card :padding="false" class="p-4">
            <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-text-muted">Cart</h2>
            @forelse ($cart as $lineId => $line)
                <div class="flex items-start justify-between gap-2 border-b border-surface-border py-3 last:border-0" wire:key="cart-line-{{ $lineId }}">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-text-primary">
                            {{ $line['name'] }}
                            @if ($line['is_pack'] ?? false)
                                <x-badge variant="success" class="ml-1">Bulk</x-badge>
                            @endif
                        </p>
                        <p class="font-tabular text-xs text-text-muted">KES {{ number_format($line['unit_price_cents'] / 100, 2) }} / {{ $line['selling_unit'] ?? $line['base_unit'] }}</p>

                        <div
                            class="mt-2 flex items-center gap-1.5"
                            x-data="{
                                step: {{ $line['allows_fractional'] ? '0.5' : '1' }},
                                nudge(delta) {
                                    let el = $refs.qty;
                                    let next = Math.max(0, (parseFloat(el.value) || 0) + delta);
                                    el.value = next;
                                    el.dispatchEvent(new Event('input'));
                                },
                            }"
                        >
                            <button type="button" @click="nudge(-step)" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-surface-border text-text-secondary hover:bg-surface-muted">
                                <x-heroicon-o-minus class="h-4 w-4" />
                            </button>
                            <input
                                x-ref="qty"
                                type="number"
                                step="{{ $line['allows_fractional'] ? '0.001' : '1' }}"
                                min="0"
                                wire:model.live.debounce.400ms="cart.{{ $lineId }}.quantity"
                                class="font-tabular h-9 w-16 rounded-lg border border-surface-border px-2 text-center text-sm"
                            >
                            <button type="button" @click="nudge(step)" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-surface-border text-text-secondary hover:bg-surface-muted">
                                <x-heroicon-o-plus class="h-4 w-4" />
                            </button>
                        </div>

                        @if ($canDiscount)
                            <div class="mt-2 flex items-center gap-2">
                                <label class="text-xs text-text-muted">Discount KES</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value="{{ $line['discount_cents'] === 0 ? '0' : number_format($line['discount_cents'] / 100, 2, '.', '') }}"
                                    wire:change="updateLineDiscount({{ $lineId }}, $event.target.value)"
                                    class="h-8 w-20 rounded-lg border border-surface-border px-2 text-sm"
                                >
                            </div>
                        @endif
                    </div>

                    <div class="flex shrink-0 flex-col items-end gap-2">
                        <p class="font-tabular text-sm font-semibold text-text-primary">
                            KES {{ number_format($this->lineTotalCents($line) / 100, 2) }}
                        </p>
                        <button wire:click="removeLine({{ $lineId }})" class="flex h-9 w-9 items-center justify-center rounded-lg text-danger-600 hover:bg-danger-50">
                            <x-heroicon-o-trash class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            @empty
                <x-empty-state icon="shopping-cart" title="Cart is empty" description="Search or scan a product to add it." class="py-8" />
            @endforelse
        </x-card>

        {{-- Payment --}}
        <x-card :padding="false" class="p-4">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-text-muted">Payment</h2>
                <div class="flex flex-wrap gap-1.5">
                    <button wire:click="addPayment('cash')" class="rounded-lg bg-surface-muted px-2.5 py-1.5 text-xs font-medium text-text-secondary hover:bg-primary-100 hover:text-primary-700">+ Cash</button>
                    <button wire:click="addPayment('mpesa')" class="rounded-lg bg-surface-muted px-2.5 py-1.5 text-xs font-medium text-text-secondary hover:bg-primary-100 hover:text-primary-700">+ M-Pesa</button>
                    <button wire:click="addPayment('credit')" class="rounded-lg bg-surface-muted px-2.5 py-1.5 text-xs font-medium text-text-secondary hover:bg-primary-100 hover:text-primary-700">+ Credit</button>
                </div>
            </div>

            @error('payments') <p class="mb-2 text-sm text-danger-600">{{ $message }}</p> @enderror

            @forelse ($payments as $i => $payment)
                <div class="mb-2 flex flex-wrap items-center gap-2" wire:key="payment-{{ $i }}">
                    <span class="w-14 shrink-0 text-xs font-medium uppercase text-text-muted">{{ $payment['method'] }}</span>
                    <input type="number" step="0.01" wire:model.live.debounce.300ms="payments.{{ $i }}.amount" class="font-tabular h-9 w-24 rounded-lg border border-surface-border px-2 text-sm">
                    @if ($payment['method'] === 'mpesa')
                        <input type="text" placeholder="Txn code" wire:model.live.debounce.300ms="payments.{{ $i }}.mpesa_code" class="h-9 w-28 rounded-lg border border-surface-border px-2 text-sm">
                    @endif
                    <button wire:click="removePayment({{ $i }})" class="ml-auto flex h-9 w-9 items-center justify-center rounded-lg text-danger-600 hover:bg-danger-50">
                        <x-heroicon-o-x-mark class="h-4 w-4" />
                    </button>
                </div>
            @empty
                <p class="text-sm text-text-muted">No payment added yet.</p>
            @endforelse
        </x-card>

        {{-- Totals --}}
        <x-card :padding="false" class="p-4 text-sm">
            <div class="flex justify-between py-1"><span class="text-text-secondary">Subtotal</span><span class="font-tabular">KES {{ number_format($this->subtotalCents() / 100, 2) }}</span></div>
            @if ($this->discountCents() > 0)
                <div class="flex justify-between py-1"><span class="text-text-secondary">Discount</span><span class="font-tabular text-danger-600">-KES {{ number_format($this->discountCents() / 100, 2) }}</span></div>
            @endif
            <div class="flex justify-between border-t border-surface-border py-2 text-lg font-semibold text-text-primary"><span>Total</span><span class="font-tabular">KES {{ number_format($this->totalCents() / 100, 2) }}</span></div>
            <div class="flex justify-between py-1"><span class="text-text-secondary">Paid</span><span class="font-tabular">KES {{ number_format($this->paidCents() / 100, 2) }}</span></div>
            <div class="flex justify-between py-1 font-medium {{ $this->remainingCents() > 0 ? 'text-danger-600' : 'text-success-600' }}">
                <span>Remaining</span><span class="font-tabular">KES {{ number_format($this->remainingCents() / 100, 2) }}</span>
            </div>
        </x-card>

        <div class="flex gap-3">
            <x-button wire:click="holdSale" variant="secondary" size="lg" class="flex-1">
                Hold sale
            </x-button>
            <x-button
                wire:click="completeSale"
                variant="primary"
                size="lg"
                class="flex-1"
                :disabled="$this->remainingCents() !== 0 || empty($cart)"
            >
                Complete sale
            </x-button>
        </div>
    </div>

    {{-- Credit limit override modal --}}
    @if ($showCreditOverrideModal)
        <x-modal title="Credit limit exceeded">
            <p class="mb-4 text-sm text-text-secondary">{{ $creditLimitWarning['message'] ?? '' }}</p>
            <p class="mb-2 text-sm font-medium text-text-primary">Owner PIN required to override</p>
            <input
                type="password"
                inputmode="numeric"
                maxlength="4"
                wire:model="overridePin"
                class="w-full rounded-lg border border-surface-border px-3 py-2.5 text-sm focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600"
                placeholder="Enter owner PIN"
            >
            @error('overridePin') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror

            <x-slot:footer>
                <x-button wire:click="$set('showCreditOverrideModal', false)" variant="ghost">Cancel</x-button>
                <x-button wire:click="confirmCreditOverride" variant="danger">Override &amp; complete</x-button>
            </x-slot:footer>
        </x-modal>
    @endif
</div>
