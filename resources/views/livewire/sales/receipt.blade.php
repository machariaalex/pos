<div>
    <div class="mb-6 flex items-center justify-between print:hidden">
        <div>
            <a href="{{ route('sales.pos') }}" class="text-sm text-slate-500 hover:underline">&larr; Back to till</a>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">Sale {{ $sale->sale_number }}</h1>
        </div>
        <div class="flex gap-3">
            @if ($sale->status === 'completed')
                <button onclick="window.print()" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Print receipt
                </button>
                <button wire:click="startVoid" class="rounded-md border border-red-300 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50">
                    Void sale
                </button>
            @endif
            <a href="{{ route('sales.returns', $sale) }}" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                Process return
            </a>
        </div>
    </div>

    @if ($sale->status === 'voided')
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 print:hidden">
            <strong>Voided</strong> {{ $sale->voided_at?->format('d M Y H:i') }} by {{ $sale->approvedBy?->name }} — {{ $sale->void_reason }}
        </div>
    @endif

    @if ($sale->returns->isNotEmpty())
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 print:hidden">
            <strong>{{ $sale->returns->count() }} return(s)</strong> totalling
            KES {{ number_format($sale->returns->sum('total_refund_cents') / 100, 2) }}
        </div>
    @endif

    {{-- Printable receipt --}}
    <div class="mx-auto w-full max-w-xs rounded-lg border border-slate-200 bg-white p-4 font-mono text-xs print:max-w-none print:border-0 print:p-0" id="receipt">
        @if ($sale->status === 'voided')
            {{-- Printed even if someone bypasses the hidden print button (e.g. Ctrl+P) —
                 a voided receipt must never be indistinguishable from a valid one. --}}
            <div class="mb-2 text-center text-sm font-bold text-red-600">*** VOIDED ***</div>
        @endif
        <div class="mb-2 text-center">
            <div class="text-sm font-bold">{{ config('app.name') }}</div>
            <div>Agrovet &amp; Farm Supplies</div>
            <div>Tel: 0700-000-000</div>
        </div>
        <div class="my-2 border-t border-dashed border-slate-400"></div>
        <div>Receipt: {{ $sale->sale_number }}</div>
        <div>Date: {{ $sale->completed_at?->format('d M Y H:i') ?? $sale->created_at->format('d M Y H:i') }}</div>
        <div>Served by: {{ $sale->user->name }}</div>
        @if ($sale->customer)
            <div>Customer: {{ $sale->customer->name }}</div>
        @endif
        <div class="my-2 border-t border-dashed border-slate-400"></div>

        @foreach ($sale->lines as $line)
            <div class="flex justify-between">
                <span>{{ $line->product->name }}</span>
            </div>
            <div class="flex justify-between text-slate-600">
                <span>{{ $line->quantity }} {{ $line->product->base_unit }} @ {{ number_format($line->unit_price_cents / 100, 2) }}</span>
                <span>{{ number_format($line->line_total_cents / 100, 2) }}</span>
            </div>
            @if ($line->discount_cents > 0)
                <div class="flex justify-between text-slate-500">
                    <span>Discount</span>
                    <span>-{{ number_format($line->discount_cents / 100, 2) }}</span>
                </div>
            @endif
        @endforeach

        <div class="my-2 border-t border-dashed border-slate-400"></div>
        <div class="flex justify-between"><span>Subtotal</span><span>{{ number_format($sale->subtotal_cents / 100, 2) }}</span></div>
        @if ($sale->discount_cents > 0)
            <div class="flex justify-between"><span>Discount</span><span>-{{ number_format($sale->discount_cents / 100, 2) }}</span></div>
        @endif
        <div class="flex justify-between text-sm font-bold"><span>TOTAL (KES)</span><span>{{ number_format($sale->total_cents / 100, 2) }}</span></div>
        <div class="my-2 border-t border-dashed border-slate-400"></div>

        @foreach ($sale->payments as $payment)
            <div class="flex justify-between">
                <span>{{ str($payment->method)->upper() }}@if ($payment->mpesa_code) ({{ $payment->mpesa_code }})@endif</span>
                <span>{{ number_format($payment->amount_cents / 100, 2) }}</span>
            </div>
        @endforeach

        <div class="my-2 border-t border-dashed border-slate-400"></div>
        <div class="text-center">Thank you for your business!</div>
        <div class="text-center text-[10px] text-slate-400">Goods sold are not returnable without receipt</div>
    </div>

    @if ($showVoidModal)
        <div class="fixed inset-0 z-20 flex items-center justify-center bg-slate-900/40 px-4 print:hidden">
            <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-lg">
                <h2 class="mb-4 text-lg font-semibold text-red-700">Void sale {{ $sale->sale_number }}</h2>
                <form wire:submit="confirmVoid" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Reason (required)</label>
                        <textarea wire:model="voidReason" rows="2" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></textarea>
                        @error('voidReason') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Owner PIN</label>
                        <input type="password" inputmode="numeric" maxlength="4" wire:model="voidPin" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        @error('voidPin') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="$set('showVoidModal', false)" class="rounded-md px-4 py-2 text-sm text-slate-600 hover:bg-slate-100">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                            Confirm void
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <style>
        @media print {
            body * { visibility: hidden; }
            #receipt, #receipt * { visibility: visible; }
            #receipt { position: absolute; top: 0; left: 0; width: 72mm; }
            @page { size: 80mm auto; margin: 0; }
        }
    </style>
</div>
