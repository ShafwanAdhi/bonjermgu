<?php

namespace App\Observers;

use App\Domain\Application\TrackingStatus;
use App\Models\ApplicationTracking;
use App\Models\Scopes\ApplicationVisibilityScope;
use App\Models\TrackingStage;
use Illuminate\Support\Carbon;

/**
 * Go Live date is derived from stage 11, never typed in — AD-12.
 *
 * Putting this in a controller would mean every path that touches a stage has
 * to remember to set the date. An observer means none of them do.
 *
 * Cancelling stage 11 clears the date. Marking it again writes today's date,
 * not the one from before (docs/application-tracking.md section 8).
 */
class ApplicationTrackingObserver
{
    public function saved(ApplicationTracking $tracking): void
    {
        if ($tracking->stage_no !== TrackingStage::GO_LIVE_STAGE) {
            return;
        }

        if (! $tracking->wasChanged('status') && ! $tracking->wasRecentlyCreated) {
            return;
        }

        // The scope is about who may read an application, not about whether a
        // derived column may be written. Bypassing it here keeps the observer
        // working regardless of who is signed in.
        $application = $tracking->application()
            ->withoutGlobalScope(ApplicationVisibilityScope::class)
            ->first();

        if (! $application) {
            return;
        }

        $goLiveDate = $tracking->status === TrackingStatus::Selesai
            // Asia/Jakarta, and a date rather than a timestamp — AD-12.
            ? Carbon::now()->toDateString()
            : null;

        if ($application->go_live_date?->toDateString() === $goLiveDate) {
            return;
        }

        $application->forceFill(['go_live_date' => $goLiveDate])->saveQuietly();
    }
}
