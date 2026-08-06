<?php

namespace App\Models;

use App\Domain\Application\TrackingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationTracking extends Model
{
    protected $fillable = [
        'application_id',
        'stage_no',
        'status',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'stage_no' => 'integer',
            'status' => TrackingStatus::class,
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(TrackingStage::class, 'stage_no', 'stage_no');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
