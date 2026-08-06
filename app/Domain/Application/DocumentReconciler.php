<?php

namespace App\Domain\Application;

use App\Models\Application;
use App\Models\ApplicationDocument;
use Illuminate\Support\Facades\DB;

/**
 * Brings an application's document rows back in line with the resolver.
 *
 * Three outcomes, per docs/document-requirement.md section 8:
 *
 *     still applies   → status kept, untouched
 *     newly applies   → new row, status Belum
 *     no longer applies → row deleted
 *
 * The whole reconciliation runs in one transaction (AD-09). A half-applied
 * change would leave the application with a document set that matches neither
 * the old profile nor the new one.
 *
 * Keeping the status of what still applies is the entire reason requirements
 * have stable codes instead of positions.
 */
class DocumentReconciler
{
    /**
     * @return array{kept: int, added: int, removed: int}
     */
    public static function reconcile(Application $application, ?int $actorId = null): array
    {
        $required = DocumentRequirementResolver::resolve(
            $application->debtor_type,
            $application->spouse_income_type,
        );

        return DB::transaction(function () use ($application, $required, $actorId) {
            $existing = ApplicationDocument::where('application_id', $application->id)
                ->pluck('requirement_code')
                ->all();

            $toAdd = array_values(array_diff($required, $existing));
            $toRemove = array_values(array_diff($existing, $required));

            if ($toRemove !== []) {
                ApplicationDocument::where('application_id', $application->id)
                    ->whereIn('requirement_code', $toRemove)
                    ->delete();
            }

            if ($toAdd !== []) {
                $now = now();

                ApplicationDocument::insert(array_map(fn (string $code) => [
                    'application_id' => $application->id,
                    'requirement_code' => $code,
                    'status' => DocumentStatus::Belum->value,
                    'updated_by' => $actorId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $toAdd));
            }

            return [
                'kept' => count(array_intersect($required, $existing)),
                'added' => count($toAdd),
                'removed' => count($toRemove),
            ];
        });
    }
}
