<?php

namespace App\Actions\Sales;

use App\Models\AuditLog;
use App\Models\CustomerLedgerEntry;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\SaleReturn;
use App\Models\SaleReturnLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class ProcessReturn
{
    /**
     * @param  array<int, array{sale_line_id: int, quantity_returned: string}>  $returnLines
     */
    public function __invoke(Sale $sale, array $returnLines, string $reason, User $approver, User $processedBy): SaleReturn
    {
        if (! $sale->isCompleted()) {
            throw new RuntimeException('Only completed sales can be returned.');
        }

        if (empty($returnLines)) {
            throw new InvalidArgumentException('A return must include at least one line.');
        }

        return DB::transaction(function () use ($sale, $returnLines, $reason, $approver, $processedBy) {
            $saleReturn = SaleReturn::create([
                'sale_id' => $sale->id,
                'processed_by' => $processedBy->id,
                'approved_by' => $approver->id,
                'reason' => $reason,
                'total_refund_cents' => 0,
            ]);

            $totalRefund = 0;

            foreach ($returnLines as $rl) {
                $saleLine = SaleLine::findOrFail($rl['sale_line_id']);

                if ($saleLine->sale_id !== $sale->id) {
                    throw new InvalidArgumentException('That line does not belong to this sale.');
                }

                $quantityReturned = (string) $rl['quantity_returned'];

                if (bccomp($quantityReturned, '0', 3) <= 0) {
                    continue;
                }

                $alreadyReturned = SaleReturnLine::where('sale_line_id', $saleLine->id)->sum('quantity_returned');
                $availableToReturn = bcsub((string) $saleLine->quantity, (string) $alreadyReturned, 3);

                if (bccomp($quantityReturned, $availableToReturn, 3) > 0) {
                    throw new InvalidArgumentException(
                        "Cannot return {$quantityReturned}: only {$availableToReturn} of this line remains returnable."
                    );
                }

                // Refund proportional to how much of the line is being returned.
                $unitTotal = bcdiv((string) $saleLine->line_total_cents, (string) $saleLine->quantity, 6);
                $refundAmount = (int) round((float) bcmul($quantityReturned, $unitTotal, 6));

                // $quantityReturned is in the sale line's selling units (e.g. kg
                // for a product sold per kg out of bags received). Batches track
                // stock in base units (e.g. bags), so restock the same proportion
                // of the batch allocation's original base-unit quantity rather
                // than adding the selling-unit number directly — otherwise a
                // return on a converted-unit product inflates stock by whatever
                // the unit conversion ratio is.
                $proportionReturned = bcdiv($quantityReturned, (string) $saleLine->quantity, 6);
                $batchAllocation = $saleLine->batchAllocations()->orderBy('id')->firstOrFail();
                $batch = $batchAllocation->batch;
                $baseQuantityRestocked = bcmul((string) $batchAllocation->quantity, $proportionReturned, 3);
                $batch->quantity_remaining = bcadd((string) $batch->quantity_remaining, $baseQuantityRestocked, 3);
                $batch->save();

                SaleReturnLine::create([
                    'sale_return_id' => $saleReturn->id,
                    'sale_line_id' => $saleLine->id,
                    'quantity_returned' => $quantityReturned,
                    'refund_amount_cents' => $refundAmount,
                    'restocked_batch_id' => $batch->id,
                ]);

                $totalRefund += $refundAmount;
            }

            if ($totalRefund === 0) {
                throw new InvalidArgumentException('Nothing to return.');
            }

            $saleReturn->update(['total_refund_cents' => $totalRefund]);

            // A sale paid (even partly) on credit is refunded as a credit
            // note against the customer's balance rather than cash back.
            $hadCreditPayment = $sale->payments()->where('method', Payment::METHOD_CREDIT)->exists();

            if ($hadCreditPayment && $sale->customer_id) {
                $customer = $sale->customer;
                $newBalance = $customer->balance_cents - $totalRefund;

                CustomerLedgerEntry::create([
                    'customer_id' => $customer->id,
                    'type' => CustomerLedgerEntry::TYPE_PAYMENT,
                    'amount_cents' => $totalRefund,
                    'sale_id' => $sale->id,
                    'running_balance_cents' => $newBalance,
                    'notes' => "Credit note — return on sale {$sale->sale_number}",
                    'created_by' => $processedBy->id,
                ]);

                $customer->update(['balance_cents' => $newBalance]);
            }

            AuditLog::record(
                'sale.refunded',
                $saleReturn,
                "{$processedBy->name} processed a return on sale {$sale->sale_number} (approved by {$approver->name}): {$reason}",
                actor: $processedBy,
            );

            return $saleReturn->fresh(['lines']);
        });
    }
}
