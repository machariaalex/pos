<?php

namespace App\Actions\Reports;

use App\Models\Payment;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\User;
use Illuminate\Support\Carbon;

class ComputeDailySummary
{
    /**
     * Shop-wide (or, with $attendant, per-till) summary for one business day:
     * revenue, payment method breakdown, cash expected in the drawer, and
     * profit net of that day's returns — not just gross sales minus COGS.
     *
     * @return array{
     *     date: string, transaction_count: int, gross_sales_cents: int,
     *     returns_cents: int, net_revenue_cents: int, discount_cents: int,
     *     by_payment_method: array<string,int>, cash_expected_cents: int,
     *     cogs_cents: int, profit_cents: int,
     * }
     */
    public function __invoke(Carbon $date, ?User $attendant = null): array
    {
        $sales = Sale::where('status', Sale::STATUS_COMPLETED)
            ->whereDate('completed_at', $date)
            ->when($attendant, fn ($q) => $q->where('user_id', $attendant->id))
            ->with(['payments', 'lines.batchAllocations'])
            ->get();

        $grossSales = $sales->sum('total_cents');
        $transactionCount = $sales->count();

        $byMethod = [Payment::METHOD_CASH => 0, Payment::METHOD_MPESA => 0, Payment::METHOD_CREDIT => 0];
        foreach ($sales as $sale) {
            foreach ($sale->payments as $payment) {
                $byMethod[$payment->method] = ($byMethod[$payment->method] ?? 0) + $payment->amount_cents;
            }
        }

        $cogs = $sales->sum(fn (Sale $sale) => $sale->lines->sum(fn ($line) => $line->costCents()));

        $returns = SaleReturn::whereDate('created_at', $date)
            ->when($attendant, fn ($q) => $q->where('processed_by', $attendant->id))
            ->with(['lines.saleLine.batchAllocations', 'sale.payments'])
            ->get();

        $returnsTotal = $returns->sum('total_refund_cents');
        [$returnsCogs, $cashRefunds] = $this->summarizeReturns($returns);

        $netRevenue = $grossSales - $returnsTotal;
        $netCogs = $cogs - $returnsCogs;

        return [
            'date' => $date->toDateString(),
            'transaction_count' => $transactionCount,
            'gross_sales_cents' => $grossSales,
            'returns_cents' => $returnsTotal,
            'net_revenue_cents' => $netRevenue,
            'discount_cents' => $sales->sum('discount_cents'),
            'by_payment_method' => $byMethod,
            // A credit-financed sale's return is a ledger credit note, not
            // cash out of the drawer — every other refund is assumed cash.
            'cash_expected_cents' => $byMethod[Payment::METHOD_CASH] - $cashRefunds,
            'cogs_cents' => $netCogs,
            'profit_cents' => $netRevenue - $netCogs,
        ];
    }

    /**
     * @return array{0: int, 1: int} [cogsReversedByReturns, cashRefundTotal]
     */
    private function summarizeReturns($returns): array
    {
        $returnsCogs = 0;
        $cashRefunds = 0;

        foreach ($returns as $return) {
            $hadCreditPayment = $return->sale->payments->contains(fn ($p) => $p->method === Payment::METHOD_CREDIT);

            foreach ($return->lines as $returnLine) {
                $saleLine = $returnLine->saleLine;

                if (bccomp((string) $saleLine->quantity, '0', 3) > 0) {
                    $proportion = bcdiv((string) $returnLine->quantity_returned, (string) $saleLine->quantity, 6);
                    $returnsCogs += (int) round((float) bcmul($proportion, (string) $saleLine->costCents(), 6));
                }
            }

            if (! $hadCreditPayment) {
                $cashRefunds += $return->total_refund_cents;
            }
        }

        return [$returnsCogs, $cashRefunds];
    }
}
