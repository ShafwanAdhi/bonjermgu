<?php

use App\Domain\Application\ApplicationCreator;
use App\Domain\Application\DebtorType;
use App\Domain\Application\DocumentReconciler;
use App\Domain\Application\DocumentRequirementResolver;
use App\Domain\Application\DocumentStatus;
use App\Domain\Application\FinancingProduct;
use App\Domain\Application\SpouseIncomeType;
use App\Domain\Application\TrackingStatus;
use App\Models\AccountOfficer;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\ApplicationTracking;
use App\Models\Referral;
use App\Models\Scopes\ApplicationVisibilityScope;
use Database\Seeders\DocumentRequirementSeeder;
use Database\Seeders\TrackingStageSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(DocumentRequirementSeeder::class);
    $this->seed(TrackingStageSeeder::class);

    $this->officer = AccountOfficer::factory()->create();
    $this->referral = Referral::factory()->create();

    // Act as the owning AO so the visibility scope lets the assertions read back.
    $this->actingAs($this->officer->user);
});

function newApplication(array $overrides = []): Application
{
    return ApplicationCreator::create(array_merge([
        'account_officer_id' => test()->officer->id,
        'referral_id' => test()->referral->id,
        'financing_product' => FinancingProduct::DanaTunai,
        'debtor_name' => 'Agus Setiawan',
        'debtor_nik' => '3173051504870006',
        'debtor_birth_date' => '1987-04-15',
        'debtor_type' => DebtorType::PeroranganNonWiraswasta,
        'spouse_income_type' => SpouseIncomeType::TidakAda,
        'amount_finance' => 130_000_000,
        'unit_count' => 1,
    ], $overrides));
}

/* ---------------------------------------------------------------- Katalog */

it('seeds twenty six document requirements and eleven stages', function () {
    expect(DB::table('document_requirements')->count())->toBe(26)
        ->and(DB::table('tracking_stages')->count())->toBe(11);
});

it('seeds idempotently', function () {
    $this->seed(DocumentRequirementSeeder::class);
    $this->seed(TrackingStageSeeder::class);

    expect(DB::table('document_requirements')->count())->toBe(26)
        ->and(DB::table('tracking_stages')->count())->toBe(11);
});

/* ------------------------------------------------------------- Pembuatan */

it('generates a unique six character code on create', function () {
    $application = newApplication();

    expect($application->code)->toMatch('/^[0-9a-zA-Z]{6}$/');

    $second = newApplication(['debtor_nik' => '3173051504870007']);

    expect($second->code)->not->toBe($application->code);
});

it('creates exactly eleven tracking rows, all Belum', function () {
    $application = newApplication();

    expect($application->trackings)->toHaveCount(11)
        ->and($application->trackings->pluck('stage_no')->sort()->values()->all())->toBe(range(1, 11))
        ->and($application->trackings->every(fn ($t) => $t->status === TrackingStatus::Belum))->toBeTrue();
});

it('creates only the applicable document rows, all Belum', function () {
    $application = newApplication();

    expect($application->documents)->toHaveCount(7)
        ->and($application->documents->every(fn ($d) => $d->status === DocumentStatus::Belum))->toBeTrue();
});

it('creates eleven documents for a legal entity and no spouse rows', function () {
    $application = newApplication([
        'debtor_type' => DebtorType::BadanHukumUsaha,
        'spouse_income_type' => null,
    ]);

    expect($application->documents)->toHaveCount(11)
        ->and($application->documents->filter(
            fn ($d) => str_starts_with($d->requirement_code, 'PSG-')
        ))->toBeEmpty();
});

/* --------------------------------------------------------- Rekonsiliasi */

/*
 * The reason requirements have stable codes. A status that still applies must
 * survive a change of debtor type — docs/document-requirement.md section 8.
 */
it('keeps the status of documents that still apply', function () {
    $application = newApplication();

    ApplicationDocument::where('application_id', $application->id)
        ->whereIn('requirement_code', ['PMH-KTP', 'PMH-NPWP', 'PMH-RKR', 'PMH-SLIP'])
        ->update(['status' => DocumentStatus::Lengkap->value]);

    $application->update([
        'debtor_type' => DebtorType::PeroranganWiraswasta,
    ]);

    DocumentReconciler::reconcile($application->fresh());

    $documents = ApplicationDocument::where('application_id', $application->id)
        ->pluck('status', 'requirement_code');

    // Still applies, status kept.
    expect($documents['PMH-KTP'])->toBe(DocumentStatus::Lengkap)
        ->and($documents['PMH-NPWP'])->toBe(DocumentStatus::Lengkap)
        ->and($documents['PMH-RKR'])->toBe(DocumentStatus::Lengkap)
        // No longer applies, row gone — its Lengkap status did not move elsewhere.
        ->and($documents->has('PMH-SLIP'))->toBeFalse()
        // Newly applies, starts at Belum.
        ->and($documents['PMH-USAHA'])->toBe(DocumentStatus::Belum)
        ->and($documents['PMH-FAKTUR'])->toBe(DocumentStatus::Belum)
        ->and($documents['PMH-PROFESI'])->toBe(DocumentStatus::Belum);
});

it('reports what it kept, added, and removed', function () {
    $application = newApplication();

    $application->update(['debtor_type' => DebtorType::PeroranganWiraswasta]);

    $result = DocumentReconciler::reconcile($application->fresh());

    expect($result)->toBe(['kept' => 6, 'added' => 3, 'removed' => 1]);
});

it('adds spouse documents when the income confirmation changes', function () {
    $application = newApplication();

    expect($application->documents)->toHaveCount(7);

    $application->update(['spouse_income_type' => SpouseIncomeType::Usaha]);
    DocumentReconciler::reconcile($application->fresh());

    $codes = ApplicationDocument::where('application_id', $application->id)
        ->pluck('requirement_code');

    expect($codes)->toHaveCount(10)
        ->and($codes)->toContain('PSG-RKR', 'PSG-USAHA', 'PSG-FAKTUR');
});

it('leaves the document set matching the resolver after repeated changes', function () {
    $application = newApplication();

    foreach ([
        [DebtorType::PeroranganWiraswasta, SpouseIncomeType::Bekerja],
        [DebtorType::PeroranganNonWiraswasta, SpouseIncomeType::Profesional],
        [DebtorType::BadanHukumUsaha, null],
        [DebtorType::PeroranganWiraswasta, SpouseIncomeType::TidakAda],
    ] as [$type, $spouse]) {
        $application->update(['debtor_type' => $type, 'spouse_income_type' => $spouse]);
        DocumentReconciler::reconcile($application->fresh());

        $expected = DocumentRequirementResolver::resolve($type, $spouse);
        $actual = ApplicationDocument::where('application_id', $application->id)
            ->pluck('requirement_code')->sort()->values()->all();

        sort($expected);

        expect($actual)->toBe($expected);
    }
});

/* ------------------------------------------------------------- Go Live */

it('records the go live date when stage eleven is marked done', function () {
    $application = newApplication();

    expect($application->go_live_date)->toBeNull();

    ApplicationTracking::where('application_id', $application->id)
        ->where('stage_no', 11)
        ->first()
        ->update(['status' => TrackingStatus::Selesai]);

    expect($application->fresh()->go_live_date?->toDateString())->toBe(now()->toDateString());
});

it('clears the go live date when stage eleven is cancelled', function () {
    $application = newApplication();

    $stage = ApplicationTracking::where('application_id', $application->id)
        ->where('stage_no', 11)->first();

    $stage->update(['status' => TrackingStatus::Selesai]);
    expect($application->fresh()->go_live_date)->not->toBeNull();

    $stage->update(['status' => TrackingStatus::Belum]);
    expect($application->fresh()->go_live_date)->toBeNull();
});

it('does not set the go live date for any other stage', function (int $stageNo) {
    $application = newApplication();

    ApplicationTracking::where('application_id', $application->id)
        ->where('stage_no', $stageNo)
        ->first()
        ->update(['status' => TrackingStatus::Selesai]);

    expect($application->fresh()->go_live_date)->toBeNull();
})->with([1, 2, 5, 9, 10]);

/*
 * data-model.md section 7, rule 1: go_live_date is filled if and only if stage
 * 11 is Selesai. Stages are markable out of order, so this has to hold even
 * when earlier stages are still Belum.
 */
it('holds the go live invariant regardless of earlier stages', function () {
    $application = newApplication();

    ApplicationTracking::where('application_id', $application->id)
        ->where('stage_no', 11)
        ->first()
        ->update(['status' => TrackingStatus::Selesai]);

    $fresh = $application->fresh();
    $stageEleven = $fresh->trackings->firstWhere('stage_no', 11);

    expect($stageEleven->status)->toBe(TrackingStatus::Selesai)
        ->and($fresh->go_live_date)->not->toBeNull()
        // Earlier stages untouched — order is not enforced.
        ->and($fresh->trackings->where('stage_no', '<', 11)
            ->every(fn ($t) => $t->status === TrackingStatus::Belum))->toBeTrue();
});

/* ------------------------------------------------------------ Constraint */

it('refuses a spouse income type on a legal entity at the database level', function () {
    Application::withoutGlobalScope(ApplicationVisibilityScope::class)->create([
        'account_officer_id' => $this->officer->id,
        'referral_id' => $this->referral->id,
        'financing_product' => FinancingProduct::DanaTunai,
        'debtor_name' => 'CV Maju Jaya',
        'debtor_nik' => '3173050208750009',
        'debtor_birth_date' => '1975-08-02',
        'debtor_type' => DebtorType::BadanHukumUsaha,
        'spouse_income_type' => SpouseIncomeType::Bekerja,
        'unit_count' => 1,
    ]);
})->throws(QueryException::class);

it('refuses a negative amount finance at the database level', function () {
    newApplication(['amount_finance' => -1]);
})->throws(QueryException::class);

it('refuses a unit count below one at the database level', function () {
    newApplication(['unit_count' => 0]);
})->throws(QueryException::class);
