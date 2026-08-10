<?php

use App\Domain\Application\ApplicationCreator;
use App\Domain\Application\DebtorType;
use App\Domain\Application\DocumentStatus;
use App\Domain\Application\FinancingProduct;
use App\Domain\Application\SpouseIncomeType;
use App\Domain\Application\TrackingStatus;
use App\Livewire\Application\ApplicationDetail;
use App\Livewire\Application\ApplicationList;
use App\Livewire\Application\CreateApplication;
use App\Models\AccountOfficer;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\ApplicationTracking;
use App\Models\Referral;
use App\Models\Scopes\ApplicationVisibilityScope;
use Database\Seeders\DocumentRequirementSeeder;
use Database\Seeders\TrackingStageSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(DocumentRequirementSeeder::class);
    $this->seed(TrackingStageSeeder::class);

    $this->officer = AccountOfficer::factory()->create(['full_name' => 'Andi Prasetyo']);
    $this->otherOfficer = AccountOfficer::factory()->create();
    $this->referral = Referral::factory()->create(['full_name' => 'Budi Santoso']);
});

function makeApplication(array $overrides = []): Application
{
    return ApplicationCreator::create(array_merge([
        'account_officer_id' => test()->officer->id,
        'referral_id' => test()->referral->id,
        'financing_product' => FinancingProduct::DanaTunai,
        'debtor_name' => 'Siti Rahayu',
        'debtor_nik' => '3173054505880002',
        'debtor_birth_date' => '1988-05-05',
        'debtor_type' => DebtorType::PeroranganNonWiraswasta,
        'spouse_income_type' => SpouseIncomeType::TidakAda,
        'amount_finance' => 120_000_000,
        'unit_count' => 1,
    ], $overrides));
}

/* ------------------------------------------------------------ Buat Aplikasi */

it('creates an application, its documents, and eleven stages from the form', function () {
    Livewire::actingAs($this->officer->user)
        ->test(CreateApplication::class)
        ->set('financing_product', FinancingProduct::MobilBekas->value)
        ->set('debtor_name', 'Agus Setiawan')
        ->set('debtor_nik', '3173051504870006')
        ->set('debtor_birth_date', '1987-04-15')
        ->set('debtor_type', DebtorType::PeroranganWiraswasta->value)
        ->set('spouse_income_type', SpouseIncomeType::Bekerja->value)
        ->set('referral_id', $this->referral->id)
        ->set('amount_finance', 'Rp 130.000.000')
        ->call('save')
        ->assertHasNoErrors();

    $this->actingAs($this->officer->user);
    $application = Application::where('debtor_nik', '3173051504870006')->first();

    expect($application)->not->toBeNull()
        ->and($application->code)->toMatch('/^[0-9a-zA-Z]{6}$/')
        ->and($application->amount_finance)->toBe(130_000_000)
        ->and($application->account_officer_id)->toBe($this->officer->id)
        // Wiraswasta 9 + spouse Bekerja 2 = 11
        ->and($application->documents)->toHaveCount(11)
        ->and($application->trackings)->toHaveCount(11);
});

it('requires a referral before an application can be created', function () {
    Livewire::actingAs($this->officer->user)
        ->test(CreateApplication::class)
        ->set('debtor_name', 'Agus Setiawan')
        ->set('debtor_nik', '3173051504870006')
        ->set('debtor_birth_date', '1987-04-15')
        ->call('save')
        ->assertHasErrors(['referral_id' => 'required']);

    $this->actingAs($this->officer->user);
    expect(Application::count())->toBe(0);
});

it('clears the spouse income type when the debtor becomes a legal entity', function () {
    Livewire::actingAs($this->officer->user)
        ->test(CreateApplication::class)
        ->set('debtor_type', DebtorType::BadanHukumUsaha->value)
        ->assertSet('spouse_income_type', null)
        ->assertSet('debtor_nik', '')
        ->assertSet('debtor_birth_date', '')
        ->assertDontSee('NIK Debitur')
        ->assertDontSee('Tanggal Lahir Debitur');
});

it('creates a legal entity application without debtor nik and birth date', function () {
    Livewire::actingAs($this->officer->user)
        ->test(CreateApplication::class)
        ->set('financing_product', FinancingProduct::DanaTunai->value)
        ->set('debtor_name', 'PT Maju Bersama')
        ->set('debtor_type', DebtorType::BadanHukumUsaha->value)
        ->set('referral_id', $this->referral->id)
        ->set('amount_finance', 'Rp 250.000.000')
        ->call('save')
        ->assertHasNoErrors();

    $this->actingAs($this->officer->user);

    $application = Application::where('debtor_name', 'PT Maju Bersama')->first();

    expect($application)->not->toBeNull()
        ->and($application->debtor_type)->toBe(DebtorType::BadanHukumUsaha)
        ->and($application->debtor_nik)->toBeNull()
        ->and($application->debtor_birth_date)->toBeNull()
        ->and($application->spouse_income_type)->toBeNull();
});

it('finds a referral by search without shipping the full list', function () {
    Referral::factory()->create(['full_name' => 'Zulfikar Rahman']);

    Livewire::actingAs($this->officer->user)
        ->test(CreateApplication::class)
        // Under two characters returns nothing at all.
        ->set('referralSearch', 'B')
        ->assertDontSee('Budi Santoso')
        ->set('referralSearch', 'Budi')
        ->assertSee('Budi Santoso')
        ->assertDontSee('Zulfikar Rahman');
});

it('refuses application creation to a referral', function () {
    Livewire::actingAs($this->referral->user)
        ->test(CreateApplication::class)
        ->assertForbidden();
});

/* ---------------------------------------------------------- Daftar & filter */

it('lists only the officer own applications', function () {
    $mine = makeApplication();

    Application::withoutGlobalScope(ApplicationVisibilityScope::class)->create([
        'account_officer_id' => $this->otherOfficer->id,
        'referral_id' => $this->referral->id,
        'financing_product' => 'DTN',
        'debtor_name' => 'Milik AO Lain',
        'debtor_nik' => '3173059999999999',
        'debtor_birth_date' => '1980-01-01',
        'debtor_type' => 'Perorangan Non Wiraswasta',
        'spouse_income_type' => 'Tidak Ada',
        'unit_count' => 1,
    ]);

    Livewire::actingAs($this->officer->user)
        ->test(ApplicationList::class)
        ->assertSee($mine->code)
        ->assertDontSee('Milik AO Lain');
});

it('filters by product and go live status', function () {
    $dtn = makeApplication();
    $ucf = makeApplication([
        'financing_product' => FinancingProduct::MobilBekas,
        'debtor_nik' => '3173054505880003',
        'debtor_name' => 'Hendra Gunawan',
    ]);

    Livewire::actingAs($this->officer->user)
        ->test(ApplicationList::class)
        ->set('product', FinancingProduct::MobilBekas->value)
        ->assertSee($ucf->code)
        ->assertDontSee($dtn->code)
        ->set('product', '')
        ->set('goLive', 'live')
        ->assertDontSee($dtn->code)
        ->assertDontSee($ucf->code);
});

it('searches by code and debtor name', function () {
    $application = makeApplication();

    Livewire::actingAs($this->officer->user)
        ->test(ApplicationList::class)
        ->set('search', 'Siti')
        ->assertSee($application->code)
        ->set('search', 'tidak ada debitur ini')
        ->assertDontSee($application->code)
        ->assertSee('Tidak ada aplikasi yang cocok');
});

it('shows a referral the AO column instead of the referral column', function () {
    makeApplication();

    Livewire::actingAs($this->referral->user)
        ->test(ApplicationList::class)
        ->assertSee('Nama AO')
        ->assertSee('Andi Prasetyo');
});

/* ------------------------------------------------------------------ Detail */

it('saves an edit to the application data', function () {
    $application = makeApplication();

    Livewire::actingAs($this->officer->user)
        ->test(ApplicationDetail::class, ['application' => $application])
        ->call('edit')
        ->set('debtor_name', 'Siti Rahayu Diperbarui')
        ->set('amount_finance', 'Rp 155.000.000')
        ->call('save')
        ->assertHasNoErrors();

    expect($application->fresh())
        ->debtor_name->toBe('Siti Rahayu Diperbarui')
        ->amount_finance->toBe(155_000_000);
});

/*
 * The reconciliation the whole document design exists for — a status that
 * still applies must survive the change.
 */
it('rebuilds the document list when the debtor type changes and keeps what still applies', function () {
    $application = makeApplication();

    ApplicationDocument::where('application_id', $application->id)
        ->where('requirement_code', 'PMH-KTP')
        ->update(['status' => DocumentStatus::Lengkap->value]);

    Livewire::actingAs($this->officer->user)
        ->test(ApplicationDetail::class, ['application' => $application])
        ->call('edit')
        ->set('debtor_type', DebtorType::PeroranganWiraswasta->value)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Daftar dokumen disusun ulang');

    $documents = ApplicationDocument::where('application_id', $application->id)
        ->pluck('status', 'requirement_code');

    expect($documents['PMH-KTP'])->toBe(DocumentStatus::Lengkap)
        ->and($documents->has('PMH-SLIP'))->toBeFalse()
        ->and($documents['PMH-USAHA'])->toBe(DocumentStatus::Belum);
});

it('hides personal identity fields when editing an application into a legal entity', function () {
    $application = makeApplication();

    Livewire::actingAs($this->officer->user)
        ->test(ApplicationDetail::class, ['application' => $application])
        ->call('edit')
        ->set('debtor_type', DebtorType::BadanHukumUsaha->value)
        ->assertSet('debtor_nik', '')
        ->assertSet('debtor_birth_date', '')
        ->assertDontSee('NIK Debitur')
        ->assertDontSee('Tanggal Lahir Debitur')
        ->call('save')
        ->assertHasNoErrors();

    expect($application->fresh())
        ->debtor_type->toBe(DebtorType::BadanHukumUsaha)
        ->debtor_nik->toBeNull()
        ->debtor_birth_date->toBeNull();
});

it('keeps the product locked once the application is go live', function () {
    $application = makeApplication();

    ApplicationTracking::where('application_id', $application->id)
        ->where('stage_no', 11)->first()
        ->update(['status' => TrackingStatus::Selesai]);

    Livewire::actingAs($this->officer->user)
        ->test(ApplicationDetail::class, ['application' => $application->fresh()])
        ->call('edit')
        ->set('financing_product', FinancingProduct::MobilBekas->value)
        ->call('save')
        ->assertHasNoErrors();

    expect($application->fresh()->financing_product)->toBe(FinancingProduct::DanaTunai);
});

/* --------------------------------------------------------- Status dokumen */

it('saves a document status change', function () {
    $application = makeApplication();
    $document = ApplicationDocument::where('application_id', $application->id)->first();

    Livewire::actingAs($this->officer->user)
        ->test(ApplicationDetail::class, ['application' => $application])
        ->call('setDocumentStatus', $document->id, DocumentStatus::Lengkap->value);

    expect($document->fresh())
        ->status->toBe(DocumentStatus::Lengkap)
        ->updated_by->toBe($this->officer->user->id);
});

it('lets a document status go back to Belum', function () {
    $application = makeApplication();
    $document = ApplicationDocument::where('application_id', $application->id)->first();

    $component = Livewire::actingAs($this->officer->user)
        ->test(ApplicationDetail::class, ['application' => $application]);

    $component->call('setDocumentStatus', $document->id, DocumentStatus::Lengkap->value);
    $component->call('setDocumentStatus', $document->id, DocumentStatus::Belum->value);

    expect($document->fresh()->status)->toBe(DocumentStatus::Belum);
});

/* -------------------------------------------------------- Status tracking */

it('saves a tracking status change', function () {
    $application = makeApplication();

    Livewire::actingAs($this->officer->user)
        ->test(ApplicationDetail::class, ['application' => $application])
        ->call('toggleStage', 3);

    $tracking = ApplicationTracking::where('application_id', $application->id)
        ->where('stage_no', 3)->first();

    expect($tracking->status)->toBe(TrackingStatus::Selesai)
        ->and($tracking->updated_by)->toBe($this->officer->user->id);
});

/* Stages are markable out of order — application-tracking.md §6. */
it('shows officer document and tracking updates on the referral application detail', function () {
    $application = makeApplication();
    $document = ApplicationDocument::where('application_id', $application->id)->first();
    $documentCount = ApplicationDocument::where('application_id', $application->id)->count();

    Livewire::actingAs($this->officer->user)
        ->test(ApplicationDetail::class, ['application' => $application])
        ->call('setDocumentStatus', $document->id, DocumentStatus::Lengkap->value)
        ->call('toggleStage', 3);

    Livewire::actingAs($this->referral->user)
        ->test(ApplicationDetail::class, ['application' => $application->fresh()])
        ->assertSet('canEdit', false)
        ->assertSee("1 / {$documentCount} lengkap")
        ->assertSee('1 / 11 selesai');
});

it('accepts a later stage while earlier ones are still Belum', function () {
    $application = makeApplication();

    Livewire::actingAs($this->officer->user)
        ->test(ApplicationDetail::class, ['application' => $application])
        ->call('toggleStage', 7);

    $trackings = ApplicationTracking::where('application_id', $application->id)
        ->pluck('status', 'stage_no');

    expect($trackings[7])->toBe(TrackingStatus::Selesai)
        ->and($trackings[1])->toBe(TrackingStatus::Belum)
        ->and($trackings[6])->toBe(TrackingStatus::Belum);
});

/* ------------------------------------------------------------- Go Live */

it('asks for confirmation before marking stage eleven and does not save yet', function () {
    $application = makeApplication();

    Livewire::actingAs($this->officer->user)
        ->test(ApplicationDetail::class, ['application' => $application])
        ->call('toggleStage', 11)
        ->assertSet('confirmingGoLive', true)
        ->assertSee('Actual Lending');

    // Nothing written until the operator confirms.
    expect($application->fresh()->go_live_date)->toBeNull();
});

it('records the go live date only after confirmation', function () {
    $application = makeApplication();

    Livewire::actingAs($this->officer->user)
        ->test(ApplicationDetail::class, ['application' => $application])
        ->call('toggleStage', 11)
        ->call('confirmGoLive')
        ->assertSet('confirmingGoLive', false);

    expect($application->fresh()->go_live_date?->toDateString())->toBe(now()->toDateString());
});

it('writes nothing when the go live confirmation is cancelled', function () {
    $application = makeApplication();

    Livewire::actingAs($this->officer->user)
        ->test(ApplicationDetail::class, ['application' => $application])
        ->call('toggleStage', 11)
        ->call('cancelGoLive')
        ->assertSet('confirmingGoLive', false);

    expect($application->fresh()->go_live_date)->toBeNull();

    $stage = ApplicationTracking::where('application_id', $application->id)
        ->where('stage_no', 11)->first();

    expect($stage->status)->toBe(TrackingStatus::Belum);
});

it('clears the go live date without confirmation when stage eleven is undone', function () {
    $application = makeApplication();

    $component = Livewire::actingAs($this->officer->user)
        ->test(ApplicationDetail::class, ['application' => $application]);

    $component->call('toggleStage', 11)->call('confirmGoLive');
    expect($application->fresh()->go_live_date)->not->toBeNull();

    // Un-marking needs no confirmation.
    $component->call('toggleStage', 11)->assertSet('confirmingGoLive', false);

    expect($application->fresh()->go_live_date)->toBeNull();
});

/* ------------------------------------------------------------- Otorisasi */

it('refuses a referral every write action on the detail screen', function () {
    $application = makeApplication();
    $document = ApplicationDocument::where('application_id', $application->id)->first();

    $component = Livewire::actingAs($this->referral->user)
        ->test(ApplicationDetail::class, ['application' => $application]);

    $component->assertSet('canEdit', false);
    $component->call('edit')->assertForbidden();

    Livewire::actingAs($this->referral->user)
        ->test(ApplicationDetail::class, ['application' => $application])
        ->call('toggleStage', 3)
        ->assertForbidden();

    Livewire::actingAs($this->referral->user)
        ->test(ApplicationDetail::class, ['application' => $application])
        ->call('setDocumentStatus', $document->id, DocumentStatus::Lengkap->value)
        ->assertForbidden();

    expect($document->fresh()->status)->toBe(DocumentStatus::Belum)
        ->and($application->fresh()->go_live_date)->toBeNull();
});

it('returns not found for another officer application', function () {
    $foreign = Application::withoutGlobalScope(ApplicationVisibilityScope::class)->create([
        'account_officer_id' => $this->otherOfficer->id,
        'referral_id' => $this->referral->id,
        'financing_product' => 'DTN',
        'debtor_name' => 'Bukan Milik Anda',
        'debtor_nik' => '3173058888888888',
        'debtor_birth_date' => '1980-01-01',
        'debtor_type' => 'Perorangan Non Wiraswasta',
        'spouse_income_type' => 'Tidak Ada',
        'unit_count' => 1,
    ]);

    $this->actingAs($this->officer->user)
        ->get('/applications/'.$foreign->code)
        ->assertNotFound();
});

it('renders the detail screen end to end for the owning officer', function () {
    $application = makeApplication();

    $this->actingAs($this->officer->user)
        ->get('/applications/'.$application->code)
        ->assertOk()
        ->assertSee($application->code)
        ->assertSee('Siti Rahayu')
        ->assertSee('Rp 120.000.000')
        ->assertSee('Golive &amp; Payment', escape: false);
});
