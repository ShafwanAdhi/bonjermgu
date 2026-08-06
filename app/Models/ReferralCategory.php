<?php

namespace App\Models;

use App\Domain\Simulation\VehicleUsage;
use App\Models\Concerns\AuditsAdminChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReferralCategory extends Model
{
    use AuditsAdminChanges;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'code',
        'segment',
        'tier',
        'allows_passenger',
        'allows_commercial',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'allows_passenger' => 'boolean',
            'allows_commercial' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @return list<VehicleUsage> */
    public function allowedVehicleUsages(): array
    {
        return array_values(array_filter([
            $this->allows_passenger ? VehicleUsage::PASSENGER : null,
            $this->allows_commercial ? VehicleUsage::COMMERCIAL : null,
        ]));
    }

    public function allowsVehicleUsage(VehicleUsage $usage): bool
    {
        return in_array($usage, $this->allowedVehicleUsages(), true);
    }

    public function subCategories(): HasMany
    {
        return $this->hasMany(ReferralSubCategory::class, 'category_id');
    }
}
