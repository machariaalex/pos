<?php

namespace App\Livewire\Inventory\StockTakes;

use App\Actions\Inventory\StartStockTake;
use App\Models\StockTake;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    public function mount(): void
    {
        Gate::authorize('adjust-stock');
    }

    public function start(StartStockTake $action)
    {
        Gate::authorize('adjust-stock');

        $stockTake = $action(auth()->user());

        return $this->redirect(route('inventory.stock-takes.show', $stockTake), navigate: false);
    }

    public function render()
    {
        return view('livewire.inventory.stock-takes.index', [
            'stockTakes' => StockTake::with(['startedBy', 'completedBy'])->latest('started_at')->paginate(15),
        ]);
    }
}
