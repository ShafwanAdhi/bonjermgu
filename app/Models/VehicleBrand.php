<?php

namespace App\Models;

use App\Models\Concerns\AuditsAdminChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleBrand extends Model
{
    use AuditsAdminChanges;

    public $timestamps = false;

    protected $fillable = ['usage_id', 'name', 'origin'];

    public function usage(): BelongsTo
    {
        return $this->belongsTo(VehicleUsage::class, 'usage_id');
    }

    public function types(): HasMany
    {
        return $this->hasMany(VehicleType::class, 'brand_id');
    }
}
