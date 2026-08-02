<?php

namespace App\Livewire\Reports;

use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class FastSlowMovers extends Component
{
    public string $dateFrom;

    public string $dateTo;

    public function mount(): void
    {
        Gate::authorize('view-reports');

        $this->dateFrom = Carbon::today()->subDays(29)->toDateString();
        $this->dateTo = Carbon::today()->toDateString();
    }

    public function render()
    {
        // Every active product, LEFT JOINed to its sales in the range, so
        // zero-sales products still show up (as the slowest movers).
        $ranked = Product::query()
            ->where('products.is_active', true)
            ->leftJoin('sale_lines', 'sale_lines.product_id', '=', 'products.id')
            ->leftJoin('sales', function ($join) {
                $join->on('sales.id', '=', 'sale_lines.sale_id')
                    ->where('sales.status', Sale::STATUS_COMPLETED)
                    ->whereDate('sales.completed_at', '>=', $this->dateFrom)
                    ->whereDate('sales.completed_at', '<=', $this->dateTo);
            })
            ->groupBy('products.id', 'products.name', 'products.base_unit')
            ->get([
                'products.name',
                'products.base_unit',
                DB::raw('coalesce(sum(case when sales.id is not null then sale_lines.quantity else 0 end), 0) as total_quantity'),
                DB::raw('coalesce(sum(case when sales.id is not null then sale_lines.line_total_cents else 0 end), 0) as total_cents'),
            ]);

        return view('livewire.reports.fast-slow-movers', [
            'fastMovers' => $ranked->sortByDesc('total_quantity')->take(10)->values(),
            'slowMovers' => $ranked->sortBy('total_quantity')->take(10)->values(),
        ]);
    }
}
