<?php

namespace App\Models;

use App\Models\Concerns\AuditsAdminChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehiclePrice extends Model
{
    use AuditsAdminChanges;

    public $timestamps = false;

    protected $fillable = ['model_id', 'year', 'price'];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'price' => 'integer',
        ];
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'model_id');
    }
}
