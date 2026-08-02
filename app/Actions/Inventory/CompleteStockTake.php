<?php

namespace App\Actions\Inventory;

use App\Models\AuditLog;
use App\Models\StockAdjustment;
use App\Models\StockTake;
use App\Models\User;
use Illuminate\Support\Carbon;

class CompleteStockTake
{
    public function __construct(private AdjustStock $adjustStock) {}

    /**
     * Apply a stock-adjustment correction for every counted line whose
     * count differs from the system quantity, then close out the take.
     * Uncounted lines are left untouched — a partial count is still valid.
     *
     * @return array{corrections: int, lines_counted: int} summary
     */
    public function __invoke(StockTake $stockTake, User $user): array
    {
        $corrections = 0;
        $linesCounted = 0;

        foreach ($stockTake->lines()->with('batch')->get() as $line) {
            if ($line->counted_quantity === null) {
                continue;
            }

            $linesCounted++;
            $variance = $line->variance();

            if (bccomp($variance, '0', 3) !== 0) {
                ($this->adjustStock)(
                    $line->batch,
                    $variance,
                    StockAdjustment::REASON_STOCK_TAKE,
                    "Stock take {$stockTake->reference} count correction",
                    $user,
                    $stockTake->id,
                );
                $corrections++;
            }
        }

        $stockTake->update([
            'status' => StockTake::STATUS_COMPLETED,
            'completed_by' => $user->id,
            'completed_at' => Carbon::now(),
        ]);

        AuditLog::record(
            'stock_take.completed',
            $stockTake,
            "{$user->name} completed stock take {$stockTake->reference}: {$linesCounted} lines counted, {$corrections} corrections applied",
            actor: $user,
        );

        return ['corrections' => $corrections, 'lines_counted' => $linesCounted];
    }
}
