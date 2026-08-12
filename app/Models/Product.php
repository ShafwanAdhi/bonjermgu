<?php

namespace App\Models;

use App\Models\Concerns\AuditsAdminChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use AuditsAdminChanges;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'dp_rate',
        'admin_min',
        'admin_max',
        'provisi_rate',
        'up_rate',
        'up_admin',
        'up_provisi',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'dp_rate' => 'float',
            'admin_min' => 'integer',
            'admin_max' => 'integer',
            'provisi_rate' => 'float',
            'up_rate' => 'float',
            'up_admin' => 'integer',
            'up_provisi' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function rates(): HasMany
    {
        return $this->hasMany(ProductRate::class);
    }
}
