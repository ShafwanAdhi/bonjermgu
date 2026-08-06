<?php

namespace App\Models;

use App\Models\Concerns\AuditsAdminChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleUsage extends Model
{
    use AuditsAdminChanges;

    public $timestamps = false;

    protected $fillable = ['name'];

    public function brands(): HasMany
    {
        return $this->hasMany(VehicleBrand::class, 'usage_id');
    }
}
