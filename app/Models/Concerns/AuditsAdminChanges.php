<?php

namespace App\Models\Concerns;

use App\Models\AdminChangeLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait AuditsAdminChanges
{
    public static function bootAuditsAdminChanges(): void
    {
        static::created(function (Model $model): void {
            self::writeAdminAudit($model, 'created', null, $model->getAttributes());
        });

        static::updated(function (Model $model): void {
            $changes = array_keys($model->getChanges());

            if ($changes === []) {
                return;
            }

            self::writeAdminAudit(
                $model,
                'updated',
                array_intersect_key($model->getRawOriginal(), array_flip($changes)),
                array_intersect_key($model->getAttributes(), array_flip($changes)),
            );
        });

        static::deleted(function (Model $model): void {
            self::writeAdminAudit($model, 'deleted', $model->getRawOriginal(), null);
        });
    }

    /** @param array<string, mixed>|null $before @param array<string, mixed>|null $after */
    private static function writeAdminAudit(
        Model $model,
        string $action,
        ?array $before,
        ?array $after,
    ): void {
        $actor = Auth::user();

        if ($actor === null || ! $actor->isAdmin()) {
            return;
        }

        AdminChangeLog::query()->create([
            'actor_id' => $actor->id,
            'actor_name' => $actor->displayName(),
            'subject_type' => $model::class,
            'subject_table' => $model->getTable(),
            'subject_id' => $model->getKey(),
            'action' => $action,
            'before_values' => $before,
            'after_values' => $after,
            'created_at' => now(),
        ]);
    }
}
