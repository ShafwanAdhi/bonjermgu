<?php

use App\Domain\Application\FinancingProduct;
use App\Models\AccountOfficer;
use App\Models\Application;
use App\Models\Referral;
use App\Models\Scopes\ApplicationVisibilityScope;
use Database\Seeders\ApplicationSeeder;
use Database\Seeders\DocumentRequirementSeeder;
use Database\Seeders\ReferralMasterSeeder;
use Database\Seeders\TrackingStageSeeder;
use Database\Seeders\UserSeeder;

beforeEach(function () {
    $this->seed(ReferralMasterSeeder::class);
    $this->seed(DocumentRequirementSeeder::class);
    $this->seed(TrackingStageSeeder::class);
    $this->seed(UserSeeder::class);
});

it('creates fifteen sample applications tied to the seeded officers and referrals', function () {
    $this->seed(ApplicationSeeder::class);

    $applications = Application::withoutGlobalScope(ApplicationVisibilityScope::class)
        ->with(['accountOfficer.user', 'referral.user', 'documents', 'trackings'])
        ->orderBy('debtor_nik')
        ->get();

    expect($applications)->toHaveCount(15)
        ->and($applications->every(fn (Application $application) => $application->accountOfficer !== null))->toBeTrue()
        ->and($applications->every(fn (Application $application) => $application->referral !== null))->toBeTrue()
        ->and($applications->every(fn (Application $application) => $application->trackings->count() === 11))->toBeTrue()
        ->and($applications->every(fn (Application $application) => $application->documents->count() > 0))->toBeTrue()
        ->and($applications->pluck('account_officer_id')->unique()->count())->toBe(AccountOfficer::count())
        ->and($applications->pluck('referral_id')->unique()->count())->toBe(Referral::count());
});

it('creates a varied portfolio with pipeline and go live data', function () {
    $this->seed(ApplicationSeeder::class);

    $applications = Application::withoutGlobalScope(ApplicationVisibilityScope::class)
        ->with(['documents', 'trackings'])
        ->get();

    expect($applications->whereNull('go_live_date')->count())->toBeGreaterThan(0)
        ->and($applications->whereNotNull('go_live_date')->count())->toBeGreaterThan(0)
        ->and($applications->pluck('financing_product')->unique()->all())->toEqualCanonicalizing([
            FinancingProduct::DanaTunai,
            FinancingProduct::MobilBekas,
        ])
        ->and($applications->whereNull('amount_finance')->count())->toBeGreaterThan(0)
        ->and($applications->whereNotNull('amount_finance')->count())->toBeGreaterThan(0)
        ->and($applications->some(fn (Application $application) => $application->completedDocumentCount() === 0))->toBeTrue()
        ->and($applications->some(fn (Application $application) => $application->completedDocumentCount() > 0))->toBeTrue()
        ->and($applications->some(fn (Application $application) => $application->completedStageCount() === 0))->toBeTrue()
        ->and($applications->some(fn (Application $application) => $application->completedStageCount() === 11))->toBeTrue();
});

it('runs idempotently without duplicating the sample applications', function () {
    $this->seed(ApplicationSeeder::class);
    $this->seed(ApplicationSeeder::class);

    $applications = Application::withoutGlobalScope(ApplicationVisibilityScope::class)->get();

    expect($applications)->toHaveCount(15)
        ->and($applications->pluck('debtor_nik')->unique()->count())->toBe(15);
});
