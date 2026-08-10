<?php

namespace App\Livewire\Reports;

use App\Models\Sale;
use App\Models\SaleLine;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class SalesReport extends Component
{
    public string $dateFrom;

    public string $dateTo;

    public string $groupBy = 'none';

    public function mount(): void
    {
        Gate::authorize('view-reports');

        $this->dateFrom = Carbon::today()->subDays(29)->toDateString();
        $this->dateTo = Carbon::today()->toDateString();
    }

    private function baseSalesQuery()
    {
        return Sale::where('status', Sale::STATUS_COMPLETED)
            ->whereDate('completed_at', '>=', $this->dateFrom)
            ->whereDate('completed_at', '<=', $this->dateTo);
    }

    public function render()
    {
        $sales = $this->baseSalesQuery()->get();

        $totals = [
            'total_cents' => $sales->sum('total_cents'),
            'transaction_count' => $sales->count(),
            'discount_cents' => $sales->sum('discount_cents'),
        ];

        $breakdown = match ($this->groupBy) {
            'product' => $this->byProduct(),
            'category' => $this->byCategory(),
            'attendant' => $this->byAttendant(),
            default => collect(),
        };

        return view('livewire.reports.sales-report', [
            'totals' => $totals,
            'breakdown' => $breakdown,
        ]);
    }

    private function byProduct()
    {
        return SaleLine::query()
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->join('products', 'products.id', '=', 'sale_lines.product_id')
            ->where('sales.status', Sale::STATUS_COMPLETED)
            ->whereDate('sales.completed_at', '>=', $this->dateFrom)
            ->whereDate('sales.completed_at', '<=', $this->dateTo)
            ->groupBy('products.id', 'products.name', 'products.base_unit', 'products.selling_unit')
            ->orderByDesc(DB::raw('sum(sale_lines.line_total_cents)'))
            ->get([
                'products.name',
                'products.base_unit',
                'products.selling_unit',
                DB::raw('sum(sale_lines.quantity) as total_quantity'),
                DB::raw('sum(sale_lines.line_total_cents) as total_cents'),
                DB::raw('sum(sale_lines.discount_cents) as discount_cents'),
            ]);
    }

    private function byCategory()
    {
        return SaleLine::query()
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->join('products', 'products.id', '=', 'sale_lines.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->where('sales.status', Sale::STATUS_COMPLETED)
            ->whereDate('sales.completed_at', '>=', $this->dateFrom)
            ->whereDate('sales.completed_at', '<=', $this->dateTo)
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc(DB::raw('sum(sale_lines.line_total_cents)'))
            ->get([
                'categories.name',
                DB::raw('sum(sale_lines.line_total_cents) as total_cents'),
                DB::raw('sum(sale_lines.discount_cents) as discount_cents'),
            ]);
    }

    private function byAttendant()
    {
        return Sale::query()
            ->join('users', 'users.id', '=', 'sales.user_id')
            ->where('sales.status', Sale::STATUS_COMPLETED)
            ->whereDate('sales.completed_at', '>=', $this->dateFrom)
            ->whereDate('sales.completed_at', '<=', $this->dateTo)
            ->groupBy('users.id', 'users.name')
            ->orderByDesc(DB::raw('sum(sales.total_cents)'))
            ->get([
                'users.name',
                DB::raw('count(sales.id) as transaction_count'),
                DB::raw('sum(sales.total_cents) as total_cents'),
                DB::raw('sum(sales.discount_cents) as discount_cents'),
            ]);
    }
}
