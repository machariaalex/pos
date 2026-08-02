<?php

namespace App\Actions\Inventory;

use App\Models\AuditLog;
use App\Models\Batch;
use App\Models\StockAdjustment;
use App\Models\User;
use InvalidArgumentException;

class AdjustStock
{
    /**
     * Apply a signed quantity delta to a batch, with a mandatory reason.
     * Every call is audit-logged; the resulting stock can never go negative.
     */
    public function __invoke(
        Batch $batch,
        string $quantityDelta,
        string $reason,
        ?string $notes,
        User $user,
        ?int $stockTakeId = null,
    ): StockAdjustment {
        if (! in_array($reason, StockAdjustment::REASONS, true)) {
            throw new InvalidArgumentException("Invalid stock adjustment reason: {$reason}");
        }

        $newRemaining = bcadd((string) $batch->quantity_remaining, $quantityDelta, 3);

        if (bccomp($newRemaining, '0', 3) < 0) {
            throw new InvalidArgumentException(
                "Adjustment would take {$batch->batch_number} below zero (currently {$batch->quantity_remaining}, delta {$quantityDelta})."
            );
        }

        $batch->quantity_remaining = $newRemaining;
        $batch->save();

        $adjustment = StockAdjustment::create([
            'batch_id' => $batch->id,
            'quantity_delta' => $quantityDelta,
            'reason' => $reason,
            'notes' => $notes,
            'stock_take_id' => $stockTakeId,
            'user_id' => $user->id,
        ]);

        AuditLog::record(
            'stock.adjusted',
            $adjustment,
            "{$user->name} adjusted {$batch->batch_number} by {$quantityDelta} ({$reason})",
            newValues: [
                'quantity_delta' => $quantityDelta,
                'reason' => $reason,
                'new_quantity_remaining' => $newRemaining,
            ],
            actor: $user,
        );

        return $adjustment;
    }
}
