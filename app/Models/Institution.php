<?php

namespace App\Models;

use App\Models\Concerns\AuditsAdminChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Institution extends Model
{
    use AuditsAdminChanges;

    public $timestamps = false;

    protected $fillable = [
        'sub_category_id',
        'name',
    ];

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(ReferralSubCategory::class, 'sub_category_id');
    }
}
