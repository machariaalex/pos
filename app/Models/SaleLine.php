<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleLine extends Model
{
    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_price_cents',
        'discount_cents',
        'line_total_cents',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price_cents' => 'integer',
            'discount_cents' => 'integer',
            'line_total_cents' => 'integer',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batchAllocations(): HasMany
    {
        return $this->hasMany(SaleLineBatch::class);
    }

    public function costCents(): int
    {
        return $this->batchAllocations->sum(
            fn (SaleLineBatch $allocation) => (int) round(bcmul((string) $allocation->quantity, (string) $allocation->unit_cost_cents, 6))
        );
    }
}
