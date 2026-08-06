<?php

namespace App\Domain\Application;

use App\Models\Application;
use App\Models\ApplicationTracking;
use App\Models\TrackingStage;
use Illuminate\Support\Facades\DB;

/**
 * Creates an application together with everything that must exist alongside it.
 *
 * Per docs/pages.md section 10, saving the form generates the code, builds the
 * applicable Document Requirement rows, and creates eleven tracking rows all
 * at status Belum. All of it in one transaction — an application with a
 * missing stage would break the invariant in data-model.md section 7.
 */
class ApplicationCreator
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function create(array $attributes, ?int $actorId = null): Application
    {
        return DB::transaction(function () use ($attributes, $actorId) {
            $application = Application::create($attributes);

            self::seedTrackings($application, $actorId);
            DocumentReconciler::reconcile($application, $actorId);

            return $application->fresh(['documents', 'trackings']);
        });
    }

    /** Eleven rows, created at once, all Belum. */
    private static function seedTrackings(Application $application, ?int $actorId): void
    {
        $now = now();

        $rows = TrackingStage::orderBy('stage_no')
            ->pluck('stage_no')
            ->map(fn (int $stageNo) => [
                'application_id' => $application->id,
                'stage_no' => $stageNo,
                'status' => TrackingStatus::Belum->value,
                'updated_by' => $actorId,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        ApplicationTracking::insert($rows);
    }
}
