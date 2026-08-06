<?php

namespace App\Models;

use App\Models\Concerns\AuditsAdminChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AgeGroup extends Model
{
    use AuditsAdminChanges;

    public $timestamps = false;

    protected $fillable = ['label', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function acpUpping(): HasOne
    {
        return $this->hasOne(AcpUpping::class);
    }
}
