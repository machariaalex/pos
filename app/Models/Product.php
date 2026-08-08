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
        'selling_unit',
        'units_per_base',
        'pack_size',
        'pack_price_cents',
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
            'units_per_base' => 'decimal:3',
            'pack_size' => 'decimal:3',
            'pack_price_cents' => 'integer',
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

    /** The unit shown to the customer on the sell screen and receipt. */
    public function effectiveSellingUnit(): string
    {
        return $this->selling_unit ?? $this->base_unit;
    }

    /** How many selling units fit in one base unit (stock unit). */
    public function effectiveUnitsPerBase(): string
    {
        return (string) ($this->units_per_base ?? '1.000');
    }

    public function allowsFractionalQuantity(): bool
    {
        return in_array($this->effectiveSellingUnit(), self::FRACTIONAL_UNITS, true);
    }

    /** Whether a discounted bulk-pack price (e.g. a whole 50kg bag) is configured. */
    public function hasBulkPack(): bool
    {
        return $this->selling_unit !== null
            && $this->pack_size !== null
            && $this->pack_price_cents !== null;
    }

    /**
     * Effective per-selling-unit price when bought as a whole pack.
     * Exact only when pack_size evenly divides pack_price_cents — true for
     * virtually every real pack size (e.g. a 50kg bag), so not worth the
     * extra complexity of guaranteeing sub-cent-exact totals for the rare
     * case it doesn't.
     */
    public function packUnitPriceCents(): int
    {
        return (int) round($this->pack_price_cents / (float) $this->pack_size);
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
