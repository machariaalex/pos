<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    public const UNIT_KG = 'kg';

    public const UNIT_G = 'g';

    public const UNIT_L = 'l';

    public const UNIT_ML = 'ml';

    public const UNIT_PCS = 'pcs';

    public const UNITS = [self::UNIT_KG, self::UNIT_G, self::UNIT_L, self::UNIT_ML, self::UNIT_PCS];

    public const FRACTIONAL_UNITS = [self::UNIT_KG, self::UNIT_G, self::UNIT_L, self::UNIT_ML];

    protected $fillable = [
        'category_id',
        'name',
        'barcode',
        'base_unit',
        'buying_price_cents',
        'selling_price_cents',
        'reorder_level',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'buying_price_cents' => 'integer',
            'selling_price_cents' => 'integer',
            'reorder_level' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    public function saleLines(): HasMany
    {
        return $this->hasMany(SaleLine::class);
    }

    public function allowsFractionalQuantity(): bool
    {
        return in_array($this->base_unit, self::FRACTIONAL_UNITS, true);
    }

    public function stockOnHand(): string
    {
        return number_format((float) $this->batches()->sum('quantity_remaining'), 3, '.', '');
    }

    public function isLowStock(): bool
    {
        return bccomp($this->stockOnHand(), (string) $this->reorder_level, 3) <= 0;
    }
}
