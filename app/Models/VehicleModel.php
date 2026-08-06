<?php

namespace App\Models;

use App\Models\Concerns\AuditsAdminChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleModel extends Model
{
    use AuditsAdminChanges;

    public $timestamps = false;

    protected $fillable = ['type_id', 'name'];

    public function type(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class, 'type_id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(VehiclePrice::class, 'model_id');
    }
}
