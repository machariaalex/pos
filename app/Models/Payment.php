<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const METHOD_CASH = 'cash';

    public const METHOD_MPESA = 'mpesa';

    public const METHOD_CREDIT = 'credit';

    public const METHODS = [self::METHOD_CASH, self::METHOD_MPESA, self::METHOD_CREDIT];

    protected $fillable = [
        'sale_id',
        'method',
        'amount_cents',
        'mpesa_code',
        'customer_id',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
