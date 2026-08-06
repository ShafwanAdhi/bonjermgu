<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eleven fixed stages. Not addable or removable through any interface
 * (docs/application-tracking.md section 4).
 */
class TrackingStage extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'stage_no';

    protected $keyType = 'int';

    public $incrementing = false;

    protected $fillable = [
        'stage_no',
        'name',
    ];

    public const GO_LIVE_STAGE = 11;
}
