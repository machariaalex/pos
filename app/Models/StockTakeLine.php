<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTakeLine extends Model
{
    protected $fillable = [
        'stock_take_id',
        'batch_id',
        'system_quantity',
        'counted_quantity',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'system_quantity' => 'decimal:3',
            'counted_quantity' => 'decimal:3',
        ];
    }

    public function stockTake(): BelongsTo
    {
        return $this->belongsTo(StockTake::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function variance(): ?string
    {
        if ($this->counted_quantity === null) {
            return null;
        }

        return bcsub((string) $this->counted_quantity, (string) $this->system_quantity, 3);
    }
}
