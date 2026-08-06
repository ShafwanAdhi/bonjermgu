<?php

namespace App\Models;

use App\Models\Concerns\AuditsAdminChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReferralSubCategory extends Model
{
    use AuditsAdminChanges;

    public $timestamps = false;

    protected $fillable = [
        'category_id',
        'name',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ReferralCategory::class, 'category_id');
    }

    public function institutions(): HasMany
    {
        return $this->hasMany(Institution::class, 'sub_category_id');
    }
}
