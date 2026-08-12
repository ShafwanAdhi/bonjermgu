<?php

namespace App\Models\Concerns;

use App\Models\AdminChangeLog;
use App\Repositories\MasterLookupRepository;
use App\Repositories\SimulationConfigurationRepository;
use App\Repositories\VehicleCascadeRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

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

        $values = [
            'actor_id' => $actor->id,
            'actor_name' => $actor->displayName(),
            'subject_type' => $model::class,
            'subject_table' => $model->getTable(),
            'subject_id' => $model->getKey(),
            'action' => $action,
            'before_values' => $before,
            'after_values' => $after,
            'created_at' => now(),
        ];

        if (Schema::hasColumn('admin_change_logs', 'audit_module')) {
            $values['audit_module'] = app()->bound('admin.audit_module') ? app('admin.audit_module') : null;
        }

        AdminChangeLog::query()->create($values);

        $version = (string) hrtime(true);

        Cache::forever(SimulationConfigurationRepository::CACHE_VERSION_KEY, $version);
        Cache::forever(MasterLookupRepository::CACHE_VERSION_KEY, $version);
        Cache::forever(VehicleCascadeRepository::CACHE_VERSION_KEY, $version);
    }
}
