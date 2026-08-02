<?php

namespace App\Actions\Sales;

use App\Models\AuditLog;
use App\Models\CustomerLedgerEntry;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class VoidSale
{
    /**
     * Reverse a completed sale entirely: restock every batch it drew from,
     * reverse any credit charge, and mark it voided. Requires the PIN of an
     * owner/manager entered at the terminal (verified by the caller before
     * this runs — $approver is who verified it).
     */
    public function __invoke(Sale $sale, string $reason, User $approver, User $processedBy): Sale
    {
        if (! $sale->isCompleted()) {
            throw new RuntimeException('Only completed sales can be voided.');
        }

        return DB::transaction(function () use ($sale, $reason, $approver, $processedBy) {
            foreach ($sale->lines as $line) {
                foreach ($line->batchAllocations as $allocation) {
                    $batch = $allocation->batch;
                    $batch->quantity_remaining = bcadd((string) $batch->quantity_remaining, (string) $allocation->quantity, 3);
                    $batch->save();
                }
            }

            foreach ($sale->payments as $payment) {
                if ($payment->method === Payment::METHOD_CREDIT && $sale->customer_id) {
                    $customer = $sale->customer;
                    $newBalance = $customer->balance_cents - $payment->amount_cents;

                    CustomerLedgerEntry::create([
                        'customer_id' => $customer->id,
                        'type' => CustomerLedgerEntry::TYPE_PAYMENT,
                        'amount_cents' => $payment->amount_cents,
                        'sale_id' => $sale->id,
                        'running_balance_cents' => $newBalance,
                        'notes' => "Reversal — sale {$sale->sale_number} voided",
                        'created_by' => $processedBy->id,
                    ]);

                    $customer->update(['balance_cents' => $newBalance]);
                }
            }

            $sale->update([
                'status' => Sale::STATUS_VOIDED,
                'voided_at' => now(),
                'void_reason' => $reason,
                'approved_by' => $approver->id,
            ]);

            AuditLog::record(
                'sale.voided',
                $sale,
                "{$processedBy->name} voided sale {$sale->sale_number} (approved by {$approver->name}): {$reason}",
                actor: $processedBy,
            );

            return $sale->fresh();
        });
    }
}
