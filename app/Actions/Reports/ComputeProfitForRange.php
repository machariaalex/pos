<?php

namespace App\Actions\Reports;

use App\Models\Sale;
use App\Models\SaleReturn;
use Illuminate\Support\Carbon;

class ComputeProfitForRange
{
    /**
     * Revenue and COGS over a date range, net of returns processed in that
     * same range (proportional COGS reversal, same approach as
     * ComputeDailySummary — a return doesn't just cut revenue, it also
     * gives back the cost of the returned goods).
     *
     * @return array{revenue_cents: int, cogs_cents: int, profit_cents: int, margin_percent: float, expenses_cents: int, net_profit_cents: int}
     */
    public function __invoke(Carbon $from, Carbon $to): array
    {
        $sales = Sale::where('status', Sale::STATUS_COMPLETED)
            ->whereDate('completed_at', '>=', $from)
            ->whereDate('completed_at', '<=', $to)
            ->with('lines.batchAllocations')
            ->get();

        $grossRevenue = $sales->sum('total_cents');
        $grossCogs = $sales->sum(fn (Sale $sale) => $sale->lines->sum(fn ($line) => $line->costCents()));

        $returns = SaleReturn::whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->with(['lines.saleLine.batchAllocations'])
            ->get();

        $returnsTotal = $returns->sum('total_refund_cents');
        $returnsCogs = 0;

        foreach ($returns as $return) {
            foreach ($return->lines as $returnLine) {
                $saleLine = $returnLine->saleLine;

                if (bccomp((string) $saleLine->quantity, '0', 3) > 0) {
                    $proportion = bcdiv((string) $returnLine->quantity_returned, (string) $saleLine->quantity, 6);
                    $returnsCogs += (int) round((float) bcmul($proportion, (string) $saleLine->costCents(), 6));
                }
            }
        }

        $revenue = $grossRevenue - $returnsTotal;
        $cogs = $grossCogs - $returnsCogs;
        $profit = $revenue - $cogs;
        $expenses = (new ComputeExpensesForRange)($from, $to);

        return [
            'revenue_cents' => $revenue,
            'cogs_cents' => $cogs,
            'profit_cents' => $profit,
            'margin_percent' => $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0.0,
            'expenses_cents' => $expenses,
            'net_profit_cents' => $profit - $expenses,
        ];
    }
}
