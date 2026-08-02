<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Batch extends Model
{
    protected $fillable = [
        'product_id',
        'batch_number',
        'expiry_date',
        'quantity_received',
        'quantity_remaining',
        'buying_price_cents',
        'received_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'received_at' => 'date',
            'quantity_received' => 'decimal:3',
            'quantity_remaining' => 'decimal:3',
            'buying_price_cents' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class);
    }

    /**
     * Batches with stock left, ordered soonest-expiry-first (FEFO).
     * Batches with no expiry date (e.g. equipment) sort last.
     */
    public function scopeFefoAvailable(Builder $query): Builder
    {
        return $query
            ->where('quantity_remaining', '>', 0)
            ->orderByRaw('expiry_date IS NULL')
            ->orderBy('expiry_date');
    }

    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }

    public function isExpiringSoon(int $days = 60): bool
    {
        return $this->expiry_date !== null
            && ! $this->isExpired()
            && $this->expiry_date->lte(Carbon::today()->addDays($days));
    }
}
