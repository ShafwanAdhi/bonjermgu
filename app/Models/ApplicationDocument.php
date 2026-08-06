<?php

namespace App\Models;

use App\Domain\Application\DocumentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Status of one requirement on one application.
 *
 * A row exists only for a requirement that applies. There is no status meaning
 * "not applicable" — absence carries that. And the row points at a requirement
 * CODE, never a slot position, so statuses cannot swap between documents
 * (CLAUDE.md rule 12).
 */
class ApplicationDocument extends Model
{
    protected $fillable = [
        'application_id',
        'requirement_code',
        'status',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(DocumentRequirement::class, 'requirement_code', 'code');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
