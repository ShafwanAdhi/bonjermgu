<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminChangeLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'actor_id',
        'actor_name',
        'subject_type',
        'subject_table',
        'subject_id',
        'action',
        'before_values',
        'after_values',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'before_values' => 'array',
            'after_values' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
