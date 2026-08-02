<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'credit_limit_cents',
        'balance_cents',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit_cents' => 'integer',
            'balance_cents' => 'integer',
        ];
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(CustomerLedgerEntry::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class);
    }

    public function hasCreditLimit(): bool
    {
        return ! is_null($this->credit_limit_cents);
    }

    public function wouldExceedCreditLimit(int $additionalChargeCents): bool
    {
        if (! $this->hasCreditLimit()) {
            return false;
        }

        return ($this->balance_cents + $additionalChargeCents) > $this->credit_limit_cents;
    }
}
