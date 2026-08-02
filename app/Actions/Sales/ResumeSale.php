<?php

namespace App\Actions\Sales;

use App\Models\Sale;
use RuntimeException;

class ResumeSale
{
    /**
     * Load a held sale's lines back into cart-shape and remove the held
     * record — completing the resumed cart creates a fresh completed sale,
     * so the held row (with no stock/payment effects) has no further use.
     *
     * @return array{lines: array<int, array{product_id: int, quantity: string, unit_price_cents: int, discount_cents: int}>, customer_id: ?int}
     */
    public function __invoke(Sale $sale): array
    {
        if (! $sale->isHeld()) {
            throw new RuntimeException('Only held sales can be resumed.');
        }

        $lines = $sale->lines->map(fn ($line) => [
            'product_id' => $line->product_id,
            'quantity' => (string) $line->quantity,
            'unit_price_cents' => $line->unit_price_cents,
            'discount_cents' => $line->discount_cents,
        ])->all();

        $customerId = $sale->customer_id;

        $sale->delete();

        return ['lines' => $lines, 'customer_id' => $customerId];
    }
}
