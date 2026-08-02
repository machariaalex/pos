<?php

namespace App\Actions\Inventory;

use App\Models\AuditLog;
use App\Models\Batch;
use App\Models\StockTake;
use App\Models\StockTakeLine;
use App\Models\User;
use Illuminate\Support\Carbon;

class StartStockTake
{
    /**
     * Snapshot every batch with stock on hand into a new stock take, so
     * counts entered later can be diffed against the system quantity at
     * the moment the count started (not whatever it drifts to meanwhile).
     */
    public function __invoke(User $user): StockTake
    {
        $stockTake = StockTake::create([
            'reference' => 'ST-'.Carbon::now()->format('Ymd-His'),
            'status' => StockTake::STATUS_IN_PROGRESS,
            'started_by' => $user->id,
            'started_at' => Carbon::now(),
        ]);

        $batches = Batch::where('quantity_remaining', '>', 0)
            ->with('product')
            ->orderBy('product_id')
            ->get();

        foreach ($batches as $batch) {
            StockTakeLine::create([
                'stock_take_id' => $stockTake->id,
                'batch_id' => $batch->id,
                'system_quantity' => $batch->quantity_remaining,
                'counted_quantity' => null,
            ]);
        }

        AuditLog::record(
            'stock_take.started',
            $stockTake,
            "{$user->name} started stock take {$stockTake->reference} covering {$batches->count()} batches",
            actor: $user,
        );

        return $stockTake;
    }
}
