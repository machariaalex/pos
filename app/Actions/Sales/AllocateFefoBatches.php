<?php

namespace App\Actions\Sales;

use App\Exceptions\InsufficientStockException;
use App\Models\Batch;
use App\Models\Product;

class AllocateFefoBatches
{
    /**
     * Split a needed quantity across the product's batches soonest-expiry-first,
     * spilling into the next batch when one runs out.
     *
     * @return array<int, array{batch: Batch, quantity: string}>
     */
    public function __invoke(Product $product, string $quantityNeeded): array
    {
        // Normalize to 3 decimal places up front so every returned quantity
        // has consistent precision, regardless of how the caller formatted it.
        $remaining = bcadd($quantityNeeded, '0', 3);
        $allocations = [];

        $batches = $product->batches()->fefoAvailable()->lockForUpdate()->get();

        foreach ($batches as $batch) {
            if (bccomp($remaining, '0', 3) <= 0) {
                break;
            }

            $take = bccomp((string) $batch->quantity_remaining, $remaining, 3) >= 0
                ? $remaining
                : (string) $batch->quantity_remaining;

            $allocations[] = ['batch' => $batch, 'quantity' => $take];
            $remaining = bcsub($remaining, $take, 3);
        }

        if (bccomp($remaining, '0', 3) > 0) {
            throw new InsufficientStockException(
                "Not enough stock of {$product->name}: short by {$remaining} {$product->base_unit}."
            );
        }

        return $allocations;
    }
}
