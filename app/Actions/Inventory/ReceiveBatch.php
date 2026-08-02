<?php

namespace App\Actions\Inventory;

use App\Models\AuditLog;
use App\Models\Batch;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Str;

class ReceiveBatch
{
    public function __invoke(
        Product $product,
        ?string $batchNumber,
        ?string $expiryDate,
        string $quantityReceived,
        int $buyingPriceCents,
        string $receivedAt,
        User $user,
    ): Batch {
        $batch = Batch::create([
            'product_id' => $product->id,
            'batch_number' => $batchNumber ?: $this->generateBatchNumber($product),
            'expiry_date' => $expiryDate,
            'quantity_received' => $quantityReceived,
            'quantity_remaining' => $quantityReceived,
            'buying_price_cents' => $buyingPriceCents,
            'received_at' => $receivedAt,
            'created_by' => $user->id,
        ]);

        AuditLog::record(
            'batch.received',
            $batch,
            "Received {$quantityReceived}{$product->base_unit} of {$product->name} (batch {$batch->batch_number})",
            actor: $user,
        );

        return $batch;
    }

    private function generateBatchNumber(Product $product): string
    {
        return 'LOT-'.$product->id.'-'.Str::upper(Str::random(6));
    }
}
