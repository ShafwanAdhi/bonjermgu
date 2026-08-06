<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * No category, sub-category, institution, or branch — those belong to
 * Referral only. See docs/actors.md section 7.
 */
class AccountOfficer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'full_name',
        'birth_date',
        'nik',
        'email',
        'phone',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
