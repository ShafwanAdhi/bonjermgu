<?php

namespace App\Models;

use App\Models\Concerns\AuditsAdminChanges;
use Illuminate\Database\Eloquent\Model;

class InsuranceExtensionRate extends Model
{
    use AuditsAdminChanges;

    public $timestamps = false;

    protected $fillable = ['code', 'rate'];

    protected function casts(): array
    {
        return ['rate' => 'float'];
    }
}
