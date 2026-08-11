<?php

namespace App\Http\Controllers;

use App\Domain\Application\DocumentStatus;
use App\Domain\Application\FinancingProduct;
use App\Domain\Application\TrackingStatus;
use App\Domain\Lending\LendingFilters;
use App\Domain\Lending\LendingQuery;
use App\Enums\Role;
use App\Models\AccountOfficer;
use App\Models\Application;
use App\Models\Referral;
use App\Models\Scopes\ApplicationVisibilityScope;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Dashboard content per role — docs/pages.md section 6.
 *
 * Referral and AO figures come from their own applications, and the global
 * scope already limits them to those. Admin figures come from the Lending
 * aggregate and the account counts — Admin has no other route into
 * application data.
 */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return match (Auth::user()->role) {
            Role::Referral => $this->referral(),
            Role::AccountOfficer => $this->officer(),
            Role::Admin => $this->admin(),
        };
    }

    private function referral(): View
    {
        $user = Auth::user();
        $applications = $this->scopedApplications();

        return view('referral.dashboard', [
            'isBirthday' => $user->hasBirthdayToday(),
            'carried' => (clone $applications)->count(),
            'goLive' => (clone $applications)->whereNotNull('go_live_date')->count(),
            'recent' => $applications
                ->with('accountOfficer:id,full_name')
                ->withCount($this->statusCounts())
                ->latest('created_at')
                ->limit(5)
                ->get(),
        ]);
    }

    private function officer(): View
    {
        $user = Auth::user();
        $applications = $this->scopedApplications();

        return view('officer.dashboard', [
            'isBirthday' => $user->hasBirthdayToday(),
            'handled' => (clone $applications)->count(),
            'pipeline' => (clone $applications)->whereNull('go_live_date')->count(),
            'referralCount' => (clone $applications)->distinct()->count('referral_id'),
            // Pending work first: still in Pipe Line, oldest first.
            'pending' => $applications
                ->with('referral:id,full_name')
                ->withCount($this->statusCounts())
                ->whereNull('go_live_date')
                ->oldest('created_at')
                ->limit(5)
                ->get(),
        ]);
    }

    private function admin(): View
    {
        $period = request()->query('period', 'all');
        $period = in_array($period, ['1', '3', '12', 'all'], true) ? $period : 'all';

        $periodMonths = $period === 'all' ? null : (int) $period;
        $periodFrom = $periodMonths
            ? CarbonImmutable::now()->startOfMonth()->subMonths($periodMonths - 1)->toDateString()
            : null;
        $periodTo = CarbonImmutable::now()->toDateString();

        $filters = new LendingFilters(from: $periodFrom, to: $periodTo);
        $totals = LendingQuery::totals($filters);

        return view('admin.dashboard', [
            'totals' => $totals,
            'referralAccounts' => Referral::count(),
            'officerAccounts' => AccountOfficer::count(),
            'inactiveAccounts' => User::where('is_active', false)->count(),
            'charts' => $this->adminCharts($filters, $period),
        ]);
    }

    /**
     * Read-only application aggregates for Admin dashboard charts.
     *
     * @return array<string, mixed>
     */
    private function adminCharts(LendingFilters $filters, string $period): array
    {
        $totals = LendingQuery::totals($filters);
        $totalAmount = max(1, $totals->actualAmount + $totals->pipelineAmount);
        $monthlyTrend = $this->adminMonthlyTrend($period);

        return [
            'period' => $period,
            'periodOptions' => [
                '1' => '1 bulan',
                '3' => '3 bulan',
                '12' => '12 bulan',
                'all' => 'Semua',
            ],
            'periodLabel' => match ($period) {
                '1' => '1 bulan terakhir',
                '3' => '3 bulan terakhir',
                '12' => '12 bulan terakhir',
                'all' => 'seluruh periode',
                default => 'seluruh periode',
            },
            'composition' => [
                'actualPercent' => round(($totals->actualAmount / $totalAmount) * 100, 1),
                'pipelinePercent' => round(($totals->pipelineAmount / $totalAmount) * 100, 1),
            ],
            'productMix' => $this->adminProductMix($filters),
            'monthlyTrend' => $monthlyTrend,
            'monthlyTrendAxis' => $this->adminMonthlyTrendAxis($monthlyTrend),
        ];
    }

    /**
     * @return array<int, array<string, int|float|string>>
     */
    private function adminProductMix(LendingFilters $filters): array
    {
        $rows = collect(FinancingProduct::cases())
            ->map(function (FinancingProduct $product) use ($filters) {
                $row = LendingQuery::totals(new LendingFilters(
                    from: $filters->from,
                    to: $filters->to,
                    product: $product->value,
                ));

                return [
                    'label' => $product->label(),
                    'actualUnits' => $row->actualUnits,
                    'actualAmount' => $row->actualAmount,
                    'pipelineUnits' => $row->pipelineUnits,
                    'pipelineAmount' => $row->pipelineAmount,
                    'totalAmount' => $row->actualAmount + $row->pipelineAmount,
                ];
            })
            ->all();

        $maxAmount = max(1, ...array_map(fn ($row) => $row['totalAmount'], $rows));

        return array_map(function ($row) use ($maxAmount) {
            $row['totalPercent'] = round(($row['totalAmount'] / $maxAmount) * 100, 1);
            $row['actualPercent'] = $row['totalAmount'] > 0
                ? round(($row['actualAmount'] / $row['totalAmount']) * 100, 1)
                : 0;
            $row['pipelinePercent'] = $row['totalAmount'] > 0
                ? round(($row['pipelineAmount'] / $row['totalAmount']) * 100, 1)
                : 0;

            return $row;
        }, $rows);
    }

    /**
     * @return array<int, array{label: string, amount: int, units: int, percent: float}>
     */
    private function adminMonthlyTrend(string $period): array
    {
        $months = $period === 'all' ? 12 : (int) $period;
        $start = CarbonImmutable::now()->startOfMonth()->subMonths($months - 1);

        $actualRows = Application::withoutGlobalScope(ApplicationVisibilityScope::class)
            ->whereNotNull('go_live_date')
            ->whereDate('go_live_date', '>=', $start->toDateString())
            ->select([
                DB::raw("to_char(go_live_date, 'YYYY-MM') as bucket"),
                DB::raw('SUM(COALESCE(amount_finance, 0)) as amount'),
                DB::raw('SUM(unit_count) as units'),
            ])
            ->groupBy(DB::raw("to_char(go_live_date, 'YYYY-MM')"))
            ->pluck('amount', 'bucket');

        $unitRows = Application::withoutGlobalScope(ApplicationVisibilityScope::class)
            ->whereNotNull('go_live_date')
            ->whereDate('go_live_date', '>=', $start->toDateString())
            ->select([
                DB::raw("to_char(go_live_date, 'YYYY-MM') as bucket"),
                DB::raw('SUM(unit_count) as units'),
            ])
            ->groupBy(DB::raw("to_char(go_live_date, 'YYYY-MM')"))
            ->pluck('units', 'bucket');

        $rows = [];
        for ($i = 0; $i < $months; $i++) {
            $month = $start->addMonths($i);
            $bucket = $month->format('Y-m');

            $rows[] = [
                'label' => $month->translatedFormat('M'),
                'amount' => (int) ($actualRows[$bucket] ?? 0),
                'units' => (int) ($unitRows[$bucket] ?? 0),
                'percent' => 0.0,
            ];
        }

        $maxAmount = max(1, ...array_column($rows, 'amount'));

        return array_map(function ($row) use ($maxAmount) {
            $row['percent'] = round(($row['amount'] / $maxAmount) * 100, 1);

            return $row;
        }, $rows);
    }

    /**
     * @param  array<int, array{label: string, amount: int, units: int, percent: float}>  $rows
     * @return array<int, array{label: string, percent: int}>
     */
    private function adminMonthlyTrendAxis(array $rows): array
    {
        $maxAmount = max(0, ...array_column($rows, 'amount'));
        $middleAmount = (int) round($maxAmount / 2);

        return [
            ['label' => $this->adminCompactRupiah($maxAmount), 'percent' => 100],
            ['label' => $this->adminCompactRupiah($middleAmount), 'percent' => 50],
            ['label' => 'Rp 0', 'percent' => 0],
        ];
    }

    private function adminCompactRupiah(int $amount): string
    {
        if ($amount >= 1_000_000_000) {
            return 'Rp '.$this->adminCompactNumber($amount / 1_000_000_000).' M';
        }

        if ($amount >= 1_000_000) {
            return 'Rp '.$this->adminCompactNumber($amount / 1_000_000).' jt';
        }

        if ($amount >= 1_000) {
            return 'Rp '.$this->adminCompactNumber($amount / 1_000).' rb';
        }

        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    private function adminCompactNumber(float $value): string
    {
        $decimals = abs($value - round($value)) < 0.05 ? 0 : 1;

        return number_format($value, $decimals, ',', '.');
    }

    /** @return array<string, callable|string> */
    private function statusCounts(): array
    {
        return [
            'documents',
            'documents as documents_complete_count' => fn ($q) => $q->where('status', DocumentStatus::Lengkap->value),
            'trackings as trackings_done_count' => fn ($q) => $q->where('status', TrackingStatus::Selesai->value),
        ];
    }

    private function scopedApplications()
    {
        return Application::query();
    }
}
