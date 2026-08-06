<?php

namespace App\Models;

use App\Models\Concerns\AuditsAdminChanges;
use Illuminate\Database\Eloquent\Model;

class AcpBaseRate extends Model
{
    use AuditsAdminChanges;

    public $timestamps = false;

    protected $fillable = ['tenor_years', 'rate'];

    protected function casts(): array
    {
        return [
            'tenor_years' => 'integer',
            'rate' => 'float',
        ];
    }
}
