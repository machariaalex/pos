<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = ['name', 'phone'];

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }
}
