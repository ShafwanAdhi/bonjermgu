<?php

namespace App\Models;

use App\Models\Concerns\AuditsAdminChanges;
use Illuminate\Database\Eloquent\Model;

class InsuranceCascoRate extends Model
{
    use AuditsAdminChanges;

    public $timestamps = false;

    protected $fillable = [
        'zone',
        'usage',
        'variant',
        'coverage',
        'band_min',
        'band_max',
        'rate',
    ];

    protected function casts(): array
    {
        return [
            'band_min' => 'integer',
            'band_max' => 'integer',
            'rate' => 'float',
        ];
    }
}
