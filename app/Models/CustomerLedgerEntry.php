<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerLedgerEntry extends Model
{
    public const TYPE_CHARGE = 'charge';

    public const TYPE_PAYMENT = 'payment';

    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'type',
        'amount_cents',
        'sale_id',
        'customer_payment_id',
        'running_balance_cents',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'running_balance_cents' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customerPayment(): BelongsTo
    {
        return $this->belongsTo(CustomerPayment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
