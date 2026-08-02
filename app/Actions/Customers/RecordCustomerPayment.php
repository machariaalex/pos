<?php

namespace App\Actions\Customers;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use App\Models\CustomerPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RecordCustomerPayment
{
    /**
     * Record a standalone payment against a customer's balance (not tied
     * to a specific sale) — e.g. a farmer settling up after harvest.
     */
    public function __invoke(
        Customer $customer,
        int $amountCents,
        string $method,
        ?string $mpesaCode,
        User $receivedBy,
        ?string $notes = null,
    ): CustomerPayment {
        return DB::transaction(function () use ($customer, $amountCents, $method, $mpesaCode, $receivedBy, $notes) {
            $payment = CustomerPayment::create([
                'customer_id' => $customer->id,
                'amount_cents' => $amountCents,
                'method' => $method,
                'mpesa_code' => $mpesaCode,
                'received_by' => $receivedBy->id,
                'notes' => $notes,
            ]);

            $newBalance = $customer->balance_cents - $amountCents;

            CustomerLedgerEntry::create([
                'customer_id' => $customer->id,
                'type' => CustomerLedgerEntry::TYPE_PAYMENT,
                'amount_cents' => $amountCents,
                'customer_payment_id' => $payment->id,
                'running_balance_cents' => $newBalance,
                'notes' => $notes,
                'created_by' => $receivedBy->id,
            ]);

            $customer->update(['balance_cents' => $newBalance]);

            AuditLog::record(
                'customer.payment_recorded',
                $payment,
                "{$receivedBy->name} recorded a payment of KES ".number_format($amountCents / 100, 2)." from {$customer->name}",
                actor: $receivedBy,
            );

            return $payment;
        });
    }
}
