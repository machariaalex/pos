<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReturnLine extends Model
{
    protected $fillable = [
        'sale_return_id',
        'sale_line_id',
        'quantity_returned',
        'refund_amount_cents',
        'restocked_batch_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity_returned' => 'decimal:3',
            'refund_amount_cents' => 'integer',
        ];
    }

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function saleLine(): BelongsTo
    {
        return $this->belongsTo(SaleLine::class);
    }

    public function restockedBatch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'restocked_batch_id');
    }
}
