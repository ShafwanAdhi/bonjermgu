<?php

namespace App\Models;

use App\Models\Concerns\AuditsAdminChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcpUpping extends Model
{
    use AuditsAdminChanges;

    public $timestamps = false;

    protected $fillable = ['age_group_id', 'upping'];

    protected function casts(): array
    {
        return ['upping' => 'float'];
    }

    public function ageGroup(): BelongsTo
    {
        return $this->belongsTo(AgeGroup::class);
    }
}
