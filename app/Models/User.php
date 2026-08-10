<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_OWNER = 'owner';

    public const ROLE_MANAGER = 'manager';

    public const ROLE_ATTENDANT = 'attendant';

    /**
     * Grantable permissions, keyed by the Gate name each one backs. An
     * Owner can grant any of these to a Manager or Attendant on top of
     * their role's defaults (see AppServiceProvider gate definitions).
     */
    public const array PERMISSIONS = [
        'edit-price' => 'Add & edit products, manage categories',
        'view-buying-price' => 'View buying prices & cost of goods',
        'view-profit' => 'View profit figures',
        'apply-discount' => 'Apply discounts at checkout',
        'adjust-stock' => 'Receive stock, adjust stock, run stock takes',
        'manage-credit-limit' => 'Set customer credit limits',
        'view-reports' => 'View reports',
        'view-audit-log' => 'View audit log',
        'manage-users' => 'Manage other users & permissions',
        'manage-expenses' => 'Record & manage expenses',
    ];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'permissions',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'pin_hash',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'permissions' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Always store email normalized (trimmed, lowercase) so lookups are
     * consistent regardless of database collation (SQLite is
     * case-sensitive by default; MySQL usually isn't).
     */
    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => Str::lower(trim($value)),
        );
    }

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    public function isManager(): bool
    {
        return $this->role === self::ROLE_MANAGER;
    }

    public function isAttendant(): bool
    {
        return $this->role === self::ROLE_ATTENDANT;
    }

    public function canApprove(): bool
    {
        return $this->isOwner() || $this->isManager();
    }

    public function canSeeProfit(): bool
    {
        return $this->isOwner();
    }

    public function hasPermission(string $key): bool
    {
        return in_array($key, $this->permissions ?? [], true);
    }

    public function hasPin(): bool
    {
        return ! is_null($this->pin_hash);
    }

    public function setPin(string $pin): void
    {
        $this->pin_hash = Hash::make($pin);
        $this->save();
    }

    public function verifyPin(string $pin): bool
    {
        return $this->hasPin() && Hash::check($pin, $this->pin_hash);
    }
}
