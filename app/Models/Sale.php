<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Sale extends Model
{
    public const STATUS_HELD = 'held';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_VOIDED = 'voided';

    protected $fillable = [
        'sale_number',
        'customer_id',
        'user_id',
        'status',
        'subtotal_cents',
        'discount_cents',
        'total_cents',
        'notes',
        'held_at',
        'completed_at',
        'voided_at',
        'void_reason',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_cents' => 'integer',
            'discount_cents' => 'integer',
            'total_cents' => 'integer',
            'held_at' => 'datetime',
            'completed_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SaleLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(SaleReturn::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isHeld(): bool
    {
        return $this->status === self::STATUS_HELD;
    }

    public function isVoided(): bool
    {
        return $this->status === self::STATUS_VOIDED;
    }

    /**
     * Human-friendly, date-scoped sale number. A race between two concurrent
     * requests on the same day is theoretically possible but not a concern
     * for a single-till shop — the unique index on sale_number would reject
     * a true collision rather than silently duplicate it.
     */
    public static function nextSaleNumber(): string
    {
        $today = Carbon::today();
        $countToday = self::whereDate('created_at', $today)->count();

        return 'S-'.$today->format('Ymd').'-'.str_pad((string) ($countToday + 1), 4, '0', STR_PAD_LEFT);
    }
}
