<?php

namespace App\Models;

use App\Models\Concerns\AuditsAdminChanges;
use Illuminate\Database\Eloquent\Model;

class FiduciaTier extends Model
{
    use AuditsAdminChanges;

    public $timestamps = false;

    protected $fillable = ['min_amount', 'max_amount', 'fee'];

    protected function casts(): array
    {
        return [
            'min_amount' => 'integer',
            'max_amount' => 'integer',
            'fee' => 'integer',
        ];
    }
}
