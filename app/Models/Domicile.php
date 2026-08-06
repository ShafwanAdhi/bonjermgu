<?php

namespace App\Models;

use App\Models\Concerns\AuditsAdminChanges;
use Illuminate\Database\Eloquent\Model;

class Domicile extends Model
{
    use AuditsAdminChanges;

    public $timestamps = false;

    protected $fillable = ['name', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }
}
