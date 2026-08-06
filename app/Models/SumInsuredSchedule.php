<?php

namespace App\Models;

use App\Models\Concerns\AuditsAdminChanges;
use Illuminate\Database\Eloquent\Model;

class SumInsuredSchedule extends Model
{
    use AuditsAdminChanges;

    public $timestamps = false;

    protected $fillable = ['year_index', 'percentage'];

    protected function casts(): array
    {
        return [
            'year_index' => 'integer',
            'percentage' => 'float',
        ];
    }
}
