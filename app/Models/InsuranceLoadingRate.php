<?php

namespace App\Models;

use App\Models\Concerns\AuditsAdminChanges;
use Illuminate\Database\Eloquent\Model;

class InsuranceLoadingRate extends Model
{
    use AuditsAdminChanges;

    public $timestamps = false;

    protected $fillable = ['vehicle_age', 'rate'];

    protected function casts(): array
    {
        return [
            'vehicle_age' => 'integer',
            'rate' => 'float',
        ];
    }
}
