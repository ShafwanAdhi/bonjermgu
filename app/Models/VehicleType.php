<?php

namespace App\Models;

use App\Models\Concerns\AuditsAdminChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleType extends Model
{
    use AuditsAdminChanges;

    public $timestamps = false;

    protected $fillable = ['brand_id', 'name'];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(VehicleBrand::class, 'brand_id');
    }

    public function models(): HasMany
    {
        return $this->hasMany(VehicleModel::class, 'type_id');
    }
}
