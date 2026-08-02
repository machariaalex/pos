<?php

namespace App\Actions\Reports;

use App\Models\Batch;

class ComputeStockValuation
{
    /**
     * Value all stock on hand at its buying (cost) price, per batch —
     * batches of the same product can carry different costs, so this is
     * not simply product.buying_price × total stock.
     *
     * @return array{total_cents: int, by_category: array<string,int>}
     */
    public function __invoke(): array
    {
        $total = 0;
        $byCategory = [];

        Batch::where('quantity_remaining', '>', 0)
            ->with('product.category')
            ->chunk(200, function ($batches) use (&$total, &$byCategory) {
                foreach ($batches as $batch) {
                    $value = (int) round((float) bcmul((string) $batch->quantity_remaining, (string) $batch->buying_price_cents, 6));
                    $categoryName = $batch->product->category->name;

                    $byCategory[$categoryName] = ($byCategory[$categoryName] ?? 0) + $value;
                    $total += $value;
                }
            });

        return ['total_cents' => $total, 'by_category' => $byCategory];
    }
}
