<?php

namespace App\Actions\Reports;

use App\Models\Expense;
use Illuminate\Support\Carbon;

class ComputeExpensesForRange
{
    /**
     * Total shop-wide expenses incurred between two dates (inclusive).
     * Never scoped to an individual attendant — expenses aren't per-till.
     */
    public function __invoke(Carbon $from, Carbon $to): int
    {
        return (int) Expense::whereDate('incurred_on', '>=', $from)
            ->whereDate('incurred_on', '<=', $to)
            ->sum('amount_cents');
    }
}
