<?php

namespace App\Actions\Inventory;

use App\Models\Batch;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class GetInventoryAlerts
{
    /**
     * Shared by the Dashboard's inline card and the dedicated mobile
     * Inventory Alerts page, so the two never drift out of sync.
     *
     * @return array{lowStockProducts: Collection, expiredBatches: Collection, expiringSoonBatches: Collection}
     */
    public function __invoke(): array
    {
        $lowStockProducts = Product::with('category')
            ->whereRaw(
                '(select coalesce(sum(quantity_remaining), 0) from batches where batches.product_id = products.id) <= products.reorder_level'
            )
            ->orderBy('name')
            ->get();

        $expiredBatches = Batch::with('product')
            ->where('quantity_remaining', '>', 0)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', Carbon::today())
            ->orderBy('expiry_date')
            ->get();

        $expiringSoonBatches = Batch::with('product')
            ->where('quantity_remaining', '>', 0)
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [Carbon::today(), Carbon::today()->addDays(60)])
            ->orderBy('expiry_date')
            ->get();

        return compact('lowStockProducts', 'expiredBatches', 'expiringSoonBatches');
    }
}
