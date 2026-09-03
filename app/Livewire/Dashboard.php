<?php

namespace App\Livewire;

use App\Actions\Reports\ComputeDailySummary;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Dashboard extends Component
{
    public string $chartRange = 'week';

    public function setChartRange(string $range): void
    {
        $this->chartRange = in_array($range, ['today', 'week'], true) ? $range : 'week';
    }

    public function render()
    {
        // Attendants see only their own till's totals for the day;
        // owner/manager see the whole shop's.
        $summaryScope = auth()->user()->canApprove() ? null : auth()->user();
        $summaryAction = app(ComputeDailySummary::class);
        $summary = $summaryAction(Carbon::today(), $summaryScope);
        $yesterday = $summaryAction(Carbon::yesterday(), $summaryScope);

        $lowStockProducts = Product::with('category')
            ->whereRaw(
                '(select coalesce(sum(quantity_remaining), 0) from batches where batches.product_id = products.id) <= products.reorder_level'
            )
            ->orderBy('name')
            ->get();

        $expiredBatches = Batch::with('product')
            ->where('quantity_remaining', '>', 0)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', Carbon::today())
            ->orderBy('expiry_date')
            ->get();

        $expiringSoonBatches = Batch::with('product')
            ->where('quantity_remaining', '>', 0)
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [Carbon::today(), Carbon::today()->addDays(60)])
            ->orderBy('expiry_date')
            ->get();

        // Computed once and reused for both the hero card's sparkline and
        // the main chart when it's already showing the week view, rather
        // than running the same 7-day summary loop twice per request.
        $weekSeries = $this->last7Days($summaryScope);

        return view('livewire.dashboard', [
            'summary' => $summary,
            'salesDelta' => $this->percentDelta($summary['net_revenue_cents'], $yesterday['net_revenue_cents']),
            'transactionsDelta' => $this->percentDelta($summary['transaction_count'], $yesterday['transaction_count']),
            'lowStockProducts' => $lowStockProducts,
            'expiredBatches' => $expiredBatches,
            'expiringSoonBatches' => $expiringSoonBatches,
            'outstandingDebtCents' => Customer::where('balance_cents', '>', 0)->sum('balance_cents'),
            'chartData' => $this->chartRange === 'today' ? $this->todayByHour($summaryScope) : $weekSeries,
            'heroSparkline' => $weekSeries['values'],
            'topProducts' => $this->topProductsThisWeek(),
            'recentSales' => $this->recentSales($summaryScope),
        ]);
    }

    /**
     * Null when yesterday was zero — there's no meaningful percentage to
     * show, and the view renders that as "no change" rather than a
     * misleading "+∞%" or divide-by-zero artifact.
     */
    private function percentDelta(int|float $today, int|float $yesterday): ?float
    {
        if ($yesterday == 0) {
            return $today == 0 ? 0.0 : null;
        }

        return round((($today - $yesterday) / $yesterday) * 100, 1);
    }

    private function last7Days(?User $scope): array
    {
        $summaryAction = app(ComputeDailySummary::class);
        $labels = [];
        $values = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $date->format('D');
            $values[] = $summaryAction($date, $scope)['net_revenue_cents'] / 100;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    private function todayByHour(?User $scope): array
    {
        $sales = Sale::where('status', Sale::STATUS_COMPLETED)
            ->whereDate('completed_at', Carbon::today())
            ->when($scope, fn ($q) => $q->where('user_id', $scope->id))
            ->get(['completed_at', 'total_cents']);

        $byHour = array_fill(8, 12, 0); // 08:00–19:00, a typical shop day

        foreach ($sales as $sale) {
            $hour = $sale->completed_at->hour;
            if (isset($byHour[$hour])) {
                $byHour[$hour] += $sale->total_cents / 100;
            }
        }

        return [
            'labels' => array_map(fn ($h) => sprintf('%02d:00', $h), array_keys($byHour)),
            'values' => array_values($byHour),
        ];
    }

    private function topProductsThisWeek()
    {
        return SaleLine::query()
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->join('products', 'products.id', '=', 'sale_lines.product_id')
            ->where('sales.status', Sale::STATUS_COMPLETED)
            ->whereDate('sales.completed_at', '>=', Carbon::today()->subDays(6))
            ->groupBy('products.id', 'products.name', 'products.base_unit')
            ->orderByDesc(DB::raw('sum(sale_lines.line_total_cents)'))
            ->limit(5)
            ->get([
                'products.name',
                'products.base_unit',
                DB::raw('sum(sale_lines.quantity) as total_quantity'),
                DB::raw('sum(sale_lines.line_total_cents) as total_cents'),
            ]);
    }

    private function recentSales(?User $scope)
    {
        return Sale::where('status', Sale::STATUS_COMPLETED)
            ->when($scope, fn ($q) => $q->where('user_id', $scope->id))
            ->with(['customer', 'payments'])
            ->latest('completed_at')
            ->limit(6)
            ->get();
    }
}
