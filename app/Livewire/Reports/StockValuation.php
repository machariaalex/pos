<?php

namespace App\Livewire\Reports;

use App\Actions\Reports\ComputeStockValuation;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class StockValuation extends Component
{
    public function mount(): void
    {
        Gate::authorize('view-buying-price');
    }

    public function render()
    {
        $valuation = app(ComputeStockValuation::class)();

        return view('livewire.reports.stock-valuation', [
            'total' => $valuation['total_cents'],
            'byCategory' => collect($valuation['by_category'])->sortDesc(),
        ]);
    }
}
