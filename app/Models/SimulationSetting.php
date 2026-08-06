<?php

namespace App\Models;

use App\Models\Concerns\AuditsAdminChanges;
use Illuminate\Database\Eloquent\Model;

class SimulationSetting extends Model
{
    use AuditsAdminChanges;

    public $timestamps = false;

    protected $fillable = ['key', 'value'];
}
