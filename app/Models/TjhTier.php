<?php

namespace App\Models;

use App\Models\Concerns\AuditsAdminChanges;
use Illuminate\Database\Eloquent\Model;

class TjhTier extends Model
{
    use AuditsAdminChanges;

    public $timestamps = false;

    protected $fillable = ['sequence', 'limit_amount', 'rate'];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'limit_amount' => 'integer',
            'rate' => 'float',
        ];
    }
}
