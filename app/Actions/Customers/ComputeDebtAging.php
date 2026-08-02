<?php

namespace App\Actions\Customers;

use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use Illuminate\Support\Carbon;

class ComputeDebtAging
{
    /**
     * Attribute a customer's outstanding balance to age buckets by walking
     * their ledger chronologically and matching payments to the oldest
     * open charges first (FIFO) — a customer can genuinely owe a mix of
     * old and recent debt at once, so this returns a split, not one bucket.
     *
     * @return array{current: int, days_30: int, days_60: int, days_90_plus: int} cents per bucket
     */
    public function __invoke(Customer $customer, ?Carbon $asOf = null): array
    {
        $asOf ??= Carbon::today();

        $openCharges = [];

        foreach ($customer->ledgerEntries()->orderBy('created_at')->orderBy('id')->get() as $entry) {
            if ($entry->type === CustomerLedgerEntry::TYPE_CHARGE) {
                $openCharges[] = ['remaining' => $entry->amount_cents, 'date' => $entry->created_at];

                continue;
            }

            $toConsume = $entry->amount_cents;

            foreach ($openCharges as &$charge) {
                if ($toConsume <= 0) {
                    break;
                }

                $consumed = min($charge['remaining'], $toConsume);
                $charge['remaining'] -= $consumed;
                $toConsume -= $consumed;
            }
            unset($charge);
        }

        $buckets = ['current' => 0, 'days_30' => 0, 'days_60' => 0, 'days_90_plus' => 0];

        foreach ($openCharges as $charge) {
            if ($charge['remaining'] <= 0) {
                continue;
            }

            $ageDays = $charge['date']->diffInDays($asOf);

            $bucket = match (true) {
                $ageDays <= 30 => 'current',
                $ageDays <= 60 => 'days_30',
                $ageDays <= 90 => 'days_60',
                default => 'days_90_plus',
            };

            $buckets[$bucket] += $charge['remaining'];
        }

        return $buckets;
    }
}
