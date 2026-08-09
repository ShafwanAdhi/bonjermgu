<?php

namespace App\Http\Controllers;

use App\Domain\Application\DocumentStatus;
use App\Domain\Application\TrackingStatus;
use App\Domain\Lending\LendingFilters;
use App\Domain\Lending\LendingQuery;
use App\Enums\Role;
use App\Models\AccountOfficer;
use App\Models\Application;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
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
        $totals = LendingQuery::totals(new LendingFilters);

        return view('admin.dashboard', [
            'totals' => $totals,
            'referralAccounts' => Referral::count(),
            'officerAccounts' => AccountOfficer::count(),
            'inactiveAccounts' => User::where('is_active', false)->count(),
        ]);
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
