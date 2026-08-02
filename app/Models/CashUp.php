<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashUp extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'business_date',
        'user_id',
        'expected_cash_cents',
        'declared_cash_cents',
        'variance_cents',
        'notes',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'business_date' => 'date',
            'expected_cash_cents' => 'integer',
            'declared_cash_cents' => 'integer',
            'variance_cents' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
