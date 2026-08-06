<?php

namespace App\Actions\Sales;

use App\Exceptions\CreditLimitExceededException;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\SaleLineBatch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CompleteSale
{
    public function __construct(private AllocateFefoBatches $allocateFefoBatches) {}

    /**
     * @param  array<int, array{product_id: int, quantity: string, unit_price_cents: int, discount_cents?: int}>  $lines
     * @param  array<int, array{method: string, amount_cents: int, mpesa_code?: ?string}>  $payments
     */
    public function __invoke(
        array $lines,
        ?Customer $customer,
        array $payments,
        User $cashier,
        ?string $notes = null,
        bool $overrideCreditLimit = false,
        ?User $overrideApprover = null,
    ): Sale {
        if (empty($lines)) {
            throw new InvalidArgumentException('A sale must have at least one line.');
        }

        return DB::transaction(function () use ($lines, $customer, $payments, $cashier, $notes, $overrideCreditLimit, $overrideApprover) {
            $sale = Sale::create([
                'sale_number' => Sale::nextSaleNumber(),
                'customer_id' => $customer?->id,
                'user_id' => $cashier->id,
                'status' => Sale::STATUS_COMPLETED,
                'notes' => $notes,
                'completed_at' => now(),
            ]);

            [$subtotal, $discountTotal, $total] = $this->createLines($sale, $lines, $cashier);

            $paidTotal = $this->createPayments($sale, $payments, $customer, $cashier, $overrideCreditLimit, $overrideApprover);

            if ($paidTotal !== $total) {
                throw new InvalidArgumentException(
                    'Payments (KES '.number_format($paidTotal / 100, 2).') do not match the sale total (KES '.number_format($total / 100, 2).').'
                );
            }

            $sale->update([
                'subtotal_cents' => $subtotal,
                'discount_cents' => $discountTotal,
                'total_cents' => $total,
            ]);

            AuditLog::record(
                'sale.completed',
                $sale,
                "{$cashier->name} completed sale {$sale->sale_number} for KES ".number_format($total / 100, 2),
                newValues: ['total_cents' => $total, 'discount_cents' => $discountTotal],
                actor: $cashier,
            );

            if ($discountTotal > 0) {
                AuditLog::record(
                    'sale.discount_applied',
                    $sale,
                    "{$cashier->name} applied KES ".number_format($discountTotal / 100, 2)." discount on sale {$sale->sale_number}",
                    newValues: ['discount_cents' => $discountTotal],
                    actor: $cashier,
                );
            }

            return $sale->fresh(['lines.product', 'payments']);
        });
    }

    /**
     * @return array{0: int, 1: int, 2: int} [subtotal, discountTotal, total] in cents
     */
    private function createLines(Sale $sale, array $lines, User $cashier): array
    {
        $subtotal = 0;
        $discountTotal = 0;
        $total = 0;

        foreach ($lines as $lineData) {
            $discountCents = (int) ($lineData['discount_cents'] ?? 0);

            if ($discountCents > 0 && ! $cashier->canApprove()) {
                throw new InvalidArgumentException('Discounts require manager or owner authorization.');
            }

            $product = Product::findOrFail($lineData['product_id']);
            $quantity = (string) $lineData['quantity']; // in selling units
            $unitPriceCents = (int) $lineData['unit_price_cents']; // per selling unit
            $unitsPerBase = (string) ($lineData['units_per_base'] ?? '1');

            $lineGross = (int) round((float) bcmul($quantity, (string) $unitPriceCents, 6));
            $lineTotal = $lineGross - $discountCents;

            $saleLine = SaleLine::create([
                'sale_id' => $sale->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price_cents' => $unitPriceCents,
                'discount_cents' => $discountCents,
                'line_total_cents' => $lineTotal,
            ]);

            // Convert selling-unit quantity to base-unit quantity for stock deduction.
            $baseQuantity = bcdiv($quantity, $unitsPerBase, 6);

            foreach (($this->allocateFefoBatches)($product, $baseQuantity) as $allocation) {
                $batch = $allocation['batch'];
                $qty = $allocation['quantity'];

                SaleLineBatch::create([
                    'sale_line_id' => $saleLine->id,
                    'batch_id' => $batch->id,
                    'quantity' => $qty,
                    'unit_cost_cents' => $batch->buying_price_cents,
                ]);

                $batch->quantity_remaining = bcsub((string) $batch->quantity_remaining, $qty, 3);
                $batch->save();
            }

            $subtotal += $lineGross;
            $discountTotal += $discountCents;
            $total += $lineTotal;
        }

        return [$subtotal, $discountTotal, $total];
    }

    private function createPayments(
        Sale $sale,
        array $payments,
        ?Customer $customer,
        User $cashier,
        bool $overrideCreditLimit,
        ?User $overrideApprover,
    ): int {
        $paidTotal = 0;

        foreach ($payments as $paymentData) {
            $method = $paymentData['method'];
            $amountCents = (int) $paymentData['amount_cents'];

            if ($amountCents <= 0) {
                continue;
            }

            if ($method === Payment::METHOD_CREDIT) {
                if (! $customer) {
                    throw new InvalidArgumentException('Credit payment requires a customer.');
                }

                $this->assertWithinCreditLimit($customer, $amountCents, $cashier, $overrideCreditLimit, $overrideApprover);
            }

            Payment::create([
                'sale_id' => $sale->id,
                'method' => $method,
                'amount_cents' => $amountCents,
                'mpesa_code' => $paymentData['mpesa_code'] ?? null,
                'customer_id' => $method === Payment::METHOD_CREDIT ? $customer->id : null,
            ]);

            if ($method === Payment::METHOD_CREDIT) {
                $newBalance = $customer->balance_cents + $amountCents;

                CustomerLedgerEntry::create([
                    'customer_id' => $customer->id,
                    'type' => CustomerLedgerEntry::TYPE_CHARGE,
                    'amount_cents' => $amountCents,
                    'sale_id' => $sale->id,
                    'running_balance_cents' => $newBalance,
                    'notes' => "Credit sale {$sale->sale_number}",
                    'created_by' => $cashier->id,
                ]);

                $customer->update(['balance_cents' => $newBalance]);
            }

            $paidTotal += $amountCents;
        }

        return $paidTotal;
    }

    private function assertWithinCreditLimit(
        Customer $customer,
        int $amountCents,
        User $cashier,
        bool $overrideCreditLimit,
        ?User $overrideApprover,
    ): void {
        if (! $customer->wouldExceedCreditLimit($amountCents)) {
            return;
        }

        $authorizedOverride = $overrideCreditLimit
            && ($cashier->isOwner() || ($overrideApprover && $overrideApprover->isOwner()));

        if ($authorizedOverride) {
            return;
        }

        throw new CreditLimitExceededException($customer, $customer->balance_cents + $amountCents);
    }
}
