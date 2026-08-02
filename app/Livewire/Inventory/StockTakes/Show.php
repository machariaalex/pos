<?php

namespace App\Livewire\Inventory\StockTakes;

use App\Actions\Inventory\CompleteStockTake;
use App\Models\StockTake;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public StockTake $stockTake;

    /** @var array<int, string> Keyed by stock_take_line id. */
    public array $counts = [];

    public ?array $completionSummary = null;

    public function mount(StockTake $stockTake): void
    {
        Gate::authorize('adjust-stock');

        $this->stockTake = $stockTake;

        foreach ($stockTake->lines as $line) {
            $this->counts[$line->id] = $line->counted_quantity !== null ? (string) $line->counted_quantity : '';
        }
    }

    public function saveCounts(): void
    {
        Gate::authorize('adjust-stock');

        if ($this->stockTake->status !== StockTake::STATUS_IN_PROGRESS) {
            return;
        }

        foreach ($this->counts as $lineId => $value) {
            $line = $this->stockTake->lines()->find($lineId);

            if (! $line) {
                continue;
            }

            $line->update(['counted_quantity' => $value === '' ? null : $value]);
        }
    }

    public function complete(CompleteStockTake $action): void
    {
        Gate::authorize('adjust-stock');

        if ($this->stockTake->status !== StockTake::STATUS_IN_PROGRESS) {
            return;
        }

        $this->saveCounts();

        $this->completionSummary = $action($this->stockTake, auth()->user());
        $this->stockTake->refresh();
    }

    public function render()
    {
        $lines = $this->stockTake->lines()->with(['batch.product'])->get()
            ->sortBy(fn ($line) => $line->batch->product->name);

        return view('livewire.inventory.stock-takes.show', ['lines' => $lines]);
    }
}
