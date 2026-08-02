<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustment extends Model
{
    public const REASON_DAMAGE = 'damage';

    public const REASON_EXPIRY_WRITEOFF = 'expiry_writeoff';

    public const REASON_THEFT = 'theft';

    public const REASON_COUNT_CORRECTION = 'count_correction';

    public const REASON_STOCK_TAKE = 'stock_take';

    public const REASON_INITIAL_STOCK = 'initial_stock';

    public const REASON_OTHER = 'other';

    public const REASONS = [
        self::REASON_DAMAGE,
        self::REASON_EXPIRY_WRITEOFF,
        self::REASON_THEFT,
        self::REASON_COUNT_CORRECTION,
        self::REASON_STOCK_TAKE,
        self::REASON_INITIAL_STOCK,
        self::REASON_OTHER,
    ];

    public $timestamps = false;

    protected $fillable = [
        'batch_id',
        'quantity_delta',
        'reason',
        'notes',
        'stock_take_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity_delta' => 'decimal:3',
            'created_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function stockTake(): BelongsTo
    {
        return $this->belongsTo(StockTake::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
