<?php

namespace App\Livewire\Inventory\Products;

use App\Actions\Inventory\AdjustStock;
use App\Models\Batch;
use App\Models\Product;
use App\Models\StockAdjustment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public Product $product;

    // Adjust stock form.
    public ?int $adjustingBatchId = null;

    public string $quantityDelta = '';

    public string $reason = '';

    public string $notes = '';

    public function mount(Product $product): void
    {
        $this->product = $product;
    }

    public function startAdjust(int $batchId): void
    {
        Gate::authorize('adjust-stock');

        $this->adjustingBatchId = $batchId;
        $this->quantityDelta = '';
        $this->reason = '';
        $this->notes = '';
    }

    public function adjustStock(AdjustStock $action): void
    {
        Gate::authorize('adjust-stock');

        $data = $this->validate([
            'quantityDelta' => ['required', 'numeric', 'not_in:0'],
            'reason' => ['required', Rule::in(StockAdjustment::REASONS)],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $batch = Batch::where('product_id', $this->product->id)->findOrFail($this->adjustingBatchId);

        try {
            $action($batch, (string) $data['quantityDelta'], $data['reason'], $data['notes'] ?: null, auth()->user());
        } catch (\InvalidArgumentException $e) {
            $this->addError('quantityDelta', $e->getMessage());

            return;
        }

        $this->adjustingBatchId = null;
        $this->product->refresh();
    }

    public function render()
    {
        $batches = $this->product->batches()
            ->with('createdBy')
            ->orderByRaw('quantity_remaining = 0')
            ->orderByRaw('expiry_date IS NULL')
            ->orderBy('expiry_date')
            ->get();

        $recentAdjustments = StockAdjustment::whereIn('batch_id', $this->product->batches()->pluck('id'))
            ->with(['batch', 'user'])
            ->latest('created_at')
            ->limit(20)
            ->get();

        return view('livewire.inventory.products.show', [
            'batches' => $batches,
            'recentAdjustments' => $recentAdjustments,
            'reasons' => StockAdjustment::REASONS,
        ]);
    }
}
