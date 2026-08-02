<div>
    <div class="mb-6">
        <a href="{{ route('sales.receipt', $sale) }}" class="text-sm text-slate-500 hover:underline">&larr; Sale {{ $sale->sale_number }}</a>
        <h1 class="mt-1 text-2xl font-semibold text-slate-900">Process return</h1>
    </div>

    @error('quantities') <p class="mb-4 rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">{{ $message }}</p> @enderror

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-2">Product</th>
                    <th class="px-4 py-2">Sold qty</th>
                    <th class="px-4 py-2">Already returned</th>
                    <th class="px-4 py-2">Return qty</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($sale->lines as $line)
                    @php $available = $this->availableToReturn($line->id, $line->quantity); @endphp
                    <tr>
                        <td class="px-4 py-2 text-slate-700">{{ $line->product->name }}</td>
                        <td class="px-4 py-2 text-slate-600">{{ $line->quantity }} {{ $line->product->base_unit }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $this->alreadyReturned($line->id) }}</td>
                        <td class="px-4 py-2">
                            @if (bccomp($available, '0', 3) > 0)
                                <input
                                    type="number"
                                    step="0.001"
                                    min="0"
                                    max="{{ $available }}"
                                    wire:model="quantities.{{ $line->id }}"
                                    placeholder="0"
                                    class="w-24 rounded-md border border-slate-300 px-2 py-1 text-sm"
                                >
                                <span class="text-xs text-slate-400">of {{ $available }} left</span>
                            @else
                                <span class="text-xs text-slate-400">Fully returned</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6 max-w-md space-y-4 rounded-lg border border-slate-200 bg-white p-4">
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Reason (required)</label>
            <textarea wire:model="reason" rows="2" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></textarea>
            @error('reason') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Owner/manager PIN</label>
            <input type="password" inputmode="numeric" maxlength="4" wire:model="pin" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            @error('pin') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <button wire:click="submit" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
            Process return
        </button>
    </div>
</div>
