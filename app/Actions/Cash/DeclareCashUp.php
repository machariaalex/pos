<?php

namespace App\Actions\Cash;

use App\Actions\Reports\ComputeDailySummary;
use App\Models\AuditLog;
use App\Models\CashUp;
use App\Models\User;
use Illuminate\Support\Carbon;

class DeclareCashUp
{
    public function __construct(private ComputeDailySummary $computeDailySummary) {}

    /**
     * An attendant declares what's actually in their drawer for a business
     * day; expected cash is (re)computed fresh from that attendant's sales
     * so a repeated declaration always reflects the current state.
     */
    public function __invoke(Carbon $businessDate, User $user, int $declaredCents, ?string $notes = null): CashUp
    {
        $expected = ($this->computeDailySummary)($businessDate, $user)['cash_expected_cents'];
        $variance = $declaredCents - $expected;

        // Not updateOrCreate(): the `business_date` column is date-cast and
        // stores with a time component, so a raw string in the match array
        // won't equal the stored value and would insert a duplicate instead
        // of finding the existing row — whereDate() compares correctly.
        $cashUp = CashUp::whereDate('business_date', $businessDate->toDateString())
            ->where('user_id', $user->id)
            ->first() ?? new CashUp(['business_date' => $businessDate, 'user_id' => $user->id]);

        $cashUp->fill([
            'expected_cash_cents' => $expected,
            'declared_cash_cents' => $declaredCents,
            'variance_cents' => $variance,
            'notes' => $notes,
        ])->save();

        AuditLog::record(
            'cash_up.declared',
            $cashUp,
            "{$user->name} declared cash-up for {$businessDate->toDateString()}: variance KES ".number_format($variance / 100, 2),
            actor: $user,
        );

        return $cashUp;
    }
}
