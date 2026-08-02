<?php

namespace App\Actions\Sales;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\User;
use InvalidArgumentException;

class HoldSale
{
    /**
     * Park a cart as a held sale — no stock is deducted and no payment is
     * taken until it's resumed and completed.
     *
     * @param  array<int, array{product_id: int, quantity: string, unit_price_cents: int, discount_cents?: int}>  $lines
     */
    public function __invoke(array $lines, ?Customer $customer, User $cashier, ?string $notes = null): Sale
    {
        if (empty($lines)) {
            throw new InvalidArgumentException('Cannot hold an empty cart.');
        }

        $sale = Sale::create([
            'sale_number' => Sale::nextSaleNumber(),
            'customer_id' => $customer?->id,
            'user_id' => $cashier->id,
            'status' => Sale::STATUS_HELD,
            'held_at' => now(),
            'notes' => $notes,
        ]);

        $subtotal = 0;
        $discountTotal = 0;
        $total = 0;

        foreach ($lines as $lineData) {
            $quantity = (string) $lineData['quantity'];
            $unitPriceCents = (int) $lineData['unit_price_cents'];
            $discountCents = (int) ($lineData['discount_cents'] ?? 0);

            $lineGross = (int) round((float) bcmul($quantity, (string) $unitPriceCents, 6));
            $lineTotal = $lineGross - $discountCents;

            SaleLine::create([
                'sale_id' => $sale->id,
                'product_id' => $lineData['product_id'],
                'quantity' => $quantity,
                'unit_price_cents' => $unitPriceCents,
                'discount_cents' => $discountCents,
                'line_total_cents' => $lineTotal,
            ]);

            $subtotal += $lineGross;
            $discountTotal += $discountCents;
            $total += $lineTotal;
        }

        $sale->update(['subtotal_cents' => $subtotal, 'discount_cents' => $discountTotal, 'total_cents' => $total]);

        AuditLog::record('sale.held', $sale, "{$cashier->name} held sale {$sale->sale_number}", actor: $cashier);

        return $sale;
    }
}
