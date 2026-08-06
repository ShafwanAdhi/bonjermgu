<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Credentials only. Anything role-specific belongs on the profile relation.
 */
class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'username',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
        ];
    }

    public function admin(): HasOne
    {
        return $this->hasOne(Admin::class);
    }

    public function referral(): HasOne
    {
        return $this->hasOne(Referral::class);
    }

    public function accountOfficer(): HasOne
    {
        return $this->hasOne(AccountOfficer::class);
    }

    /** The profile row matching this user's role. Exactly one always exists. */
    public function profile(): HasOne
    {
        return match ($this->role) {
            Role::Admin => $this->admin(),
            Role::Referral => $this->referral(),
            Role::AccountOfficer => $this->accountOfficer(),
        };
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isReferral(): bool
    {
        return $this->role === Role::Referral;
    }

    public function isAccountOfficer(): bool
    {
        return $this->role === Role::AccountOfficer;
    }

    public function displayName(): string
    {
        return $this->profile()->first()?->full_name ?? $this->username;
    }
}
