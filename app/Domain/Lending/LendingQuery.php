<?php

namespace App\Domain\Lending;

use App\Domain\Application\ApplicationStatus;
use App\Models\Application;
use App\Models\Scopes\ApplicationVisibilityScope;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Lending aggregation — docs/lending.md.
 *
 * Computed on every open, never stored (AD-11). A stored figure goes stale the
 * moment stage 11 is cancelled, and every sync mechanism is a new way to be
 * wrong.
 *
 * This is the ONE place withoutGlobalScope is permitted. Admin genuinely has
 * to see every application here, and only here.
 */
class LendingQuery
{
    /**
     * Actual Lending per calendar month, keyed 'YYYY-MM', for the Admin
     * dashboard trend.
     *
     * It lives here rather than in the controller for the reason above: the
     * visibility scope may only be lifted inside this aggregate, so every
     * bypass stays in one auditable place.
     *
     * A cancelled application never carries a Go Live date and cancelling is
     * blocked once one is live, so the Actual filter already excludes them.
     *
     * @return Collection<string, array{amount: int, units: int}>
     */
    public static function monthlyActualSince(string $since): Collection
    {
        return Application::withoutGlobalScope(ApplicationVisibilityScope::class)
            ->whereNotNull('go_live_date')
            ->whereDate('go_live_date', '>=', $since)
            ->groupBy(DB::raw("to_char(go_live_date, 'YYYY-MM')"))
            ->select([
                DB::raw("to_char(go_live_date, 'YYYY-MM') as bucket"),
                DB::raw('SUM(COALESCE(amount_finance, 0)) as amount'),
                DB::raw('SUM(unit_count) as units'),
            ])
            ->get()
            ->keyBy('bucket')
            ->map(fn ($row): array => ['amount' => (int) $row->amount, 'units' => (int) $row->units]);
    }

    /** Rows for the Per AO report: one row per Account Officer. */
    public static function perOfficer(LendingFilters $filters): Collection
    {
        return self::aggregate($filters, 'officer', 'account_officers.full_name');
    }

    /** Rows for the Per Referral report: one row per Referral. */
    public static function perReferral(LendingFilters $filters): Collection
    {
        return self::aggregate($filters, 'referral', 'referrals.full_name');
    }

    /**
     * A row appears only for a party with at least one application
     * (docs/lending.md section 6).
     *
     * @return Collection<int, LendingRow>
     */
    private static function aggregate(LendingFilters $filters, string $groupBy, string $nameColumn): Collection
    {
        $query = self::baseQuery($filters);

        return $query
            ->select([
                DB::raw($nameColumn.' as party_name'),
                // Period cuts Actual only. Pipe Line is a position as of now
                // and has no period basis — docs/lending.md section 7.
                DB::raw(self::actualCase($filters, 'applications.unit_count').' as actual_units'),
                DB::raw(self::actualCase($filters, 'COALESCE(applications.amount_finance, 0)').' as actual_amount'),
                DB::raw('SUM(CASE WHEN applications.go_live_date IS NULL THEN applications.unit_count ELSE 0 END) as pipeline_units'),
                DB::raw('SUM(CASE WHEN applications.go_live_date IS NULL THEN COALESCE(applications.amount_finance, 0) ELSE 0 END) as pipeline_amount'),
                DB::raw($groupBy === 'referral' ? 'referrals.id as party_id' : 'account_officers.id as party_id'),
            ])
            ->groupBy($groupBy === 'referral' ? 'referrals.id' : 'account_officers.id', DB::raw($nameColumn))
            ->orderByDesc('actual_amount')
            ->get()
            ->map(fn ($row) => new LendingRow(
                name: $row->party_name,
                actualUnits: (int) $row->actual_units,
                actualAmount: (int) $row->actual_amount,
                pipelineUnits: (int) $row->pipeline_units,
                pipelineAmount: (int) $row->pipeline_amount,
                id: (int) $row->party_id,
            ));
    }

    /** The TOTAL row. Always follows the active filters. */
    public static function totals(LendingFilters $filters): LendingRow
    {
        $row = self::baseQuery($filters)
            ->select([
                DB::raw(self::actualCase($filters, 'applications.unit_count').' as actual_units'),
                DB::raw(self::actualCase($filters, 'COALESCE(applications.amount_finance, 0)').' as actual_amount'),
                DB::raw('SUM(CASE WHEN applications.go_live_date IS NULL THEN applications.unit_count ELSE 0 END) as pipeline_units'),
                DB::raw('SUM(CASE WHEN applications.go_live_date IS NULL THEN COALESCE(applications.amount_finance, 0) ELSE 0 END) as pipeline_amount'),
            ])
            ->first();

        return new LendingRow(
            name: 'TOTAL',
            actualUnits: (int) ($row->actual_units ?? 0),
            actualAmount: (int) ($row->actual_amount ?? 0),
            pipelineUnits: (int) ($row->pipeline_units ?? 0),
            pipelineAmount: (int) ($row->pipeline_amount ?? 0),
        );
    }

    /**
     * SUM over Actual rows, with the period cut applied to Go Live date.
     * An application without an Amount Finance counts 0 in money but still
     * counts in units — docs/lending.md section 4.
     */
    private static function actualCase(LendingFilters $filters, string $expression): string
    {
        $condition = 'applications.go_live_date IS NOT NULL';

        if ($filters->from) {
            $condition .= " AND applications.go_live_date >= '".$filters->from."'";
        }

        if ($filters->to) {
            $condition .= " AND applications.go_live_date <= '".$filters->to."'";
        }

        return "SUM(CASE WHEN {$condition} THEN {$expression} ELSE 0 END)";
    }

    private static function baseQuery(LendingFilters $filters): QueryBuilder
    {
        $query = Application::withoutGlobalScope(ApplicationVisibilityScope::class)
            ->getQuery()
            ->from('applications')
            ->join('referrals', 'referrals.id', '=', 'applications.referral_id')
            ->join('account_officers', 'account_officers.id', '=', 'applications.account_officer_id')
            // A cancelled application has no Go Live date, so without this it
            // lands in Pipe Line and inflates the position. Cancelling is
            // blocked once an application is live, so nothing Actual is lost
            // here.
            ->where('applications.application_status', '!=', ApplicationStatus::Canceled->value);

        if ($filters->product) {
            $query->where('applications.financing_product', $filters->product);
        }

        // Referral attributes narrow the rows; grouping stays at account level
        // (docs/lending.md section 8).
        if ($filters->categoryId) {
            $query->where('referrals.category_id', $filters->categoryId);
        }

        if ($filters->subCategoryId) {
            $query->where('referrals.sub_category_id', $filters->subCategoryId);
        }

        if ($filters->institutionId) {
            $query->where('referrals.institution_id', $filters->institutionId);
        }

        return $query;
    }
}
