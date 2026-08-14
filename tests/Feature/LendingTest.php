<?php

use App\Domain\Application\ApplicationStatus;
use App\Domain\Lending\LendingFilters;
use App\Domain\Lending\LendingQuery;
use App\Models\AccountOfficer;
use App\Models\Admin;
use App\Models\Application;
use App\Models\Referral;
use App\Models\Scopes\ApplicationVisibilityScope;
use App\Models\User;

beforeEach(function () {
    $this->officerA = AccountOfficer::factory()->create(['full_name' => 'Andi Prasetyo']);
    $this->officerB = AccountOfficer::factory()->create(['full_name' => 'Rina Marlina']);
    $this->referralX = Referral::factory()->create(['full_name' => 'Budi Santoso']);
    $this->referralY = Referral::factory()->create(['full_name' => 'Eka Putri']);
});

function lendingApplication(
    AccountOfficer $officer,
    Referral $referral,
    ?int $amount,
    ?string $goLiveDate = null,
    int $units = 1,
    string $product = 'DTN',
): Application {
    return Application::withoutGlobalScope(ApplicationVisibilityScope::class)->create([
        'account_officer_id' => $officer->id,
        'referral_id' => $referral->id,
        'financing_product' => $product,
        'debtor_name' => fake()->name(),
        'debtor_nik' => fake()->unique()->numerify('317305##########'),
        'debtor_birth_date' => '1985-01-01',
        'debtor_type' => 'Perorangan Non Wiraswasta',
        'spouse_income_type' => 'Tidak Ada',
        'amount_finance' => $amount,
        'unit_count' => $units,
        'go_live_date' => $goLiveDate,
    ]);
}

/* ---------------------------------------------------------- Klasifikasi */

it('splits applications into actual and pipe line by go live date', function () {
    lendingApplication($this->officerA, $this->referralX, 100_000_000, '2026-06-15');
    lendingApplication($this->officerA, $this->referralX, 50_000_000, null);

    $totals = LendingQuery::totals(new LendingFilters);

    expect($totals->actualUnits)->toBe(1)
        ->and($totals->actualAmount)->toBe(100_000_000)
        ->and($totals->pipelineUnits)->toBe(1)
        ->and($totals->pipelineAmount)->toBe(50_000_000);
});

/*
 * An application without an Amount Finance counts 0 in money but still counts
 * as a unit — docs/lending.md section 4.
 */
it('counts an application without amount finance as a unit but zero money', function () {
    lendingApplication($this->officerA, $this->referralX, null, '2026-06-15');

    $totals = LendingQuery::totals(new LendingFilters);

    expect($totals->actualUnits)->toBe(1)
        ->and($totals->actualAmount)->toBe(0);
});

/*
 * A cancelled application has no Go Live date either, so filtering on that
 * column alone leaves it counted as Pipe Line and overstates the position
 * Admin reads off the report.
 */
it('keeps a cancelled application out of pipe line', function () {
    lendingApplication($this->officerA, $this->referralX, 50_000_000, null);
    lendingApplication($this->officerA, $this->referralX, 90_000_000, null)
        ->update(['application_status' => ApplicationStatus::Canceled]);

    $totals = LendingQuery::totals(new LendingFilters);

    expect($totals->pipelineUnits)->toBe(1)
        ->and($totals->pipelineAmount)->toBe(50_000_000);
});

it('drops a party whose applications are all cancelled', function () {
    lendingApplication($this->officerA, $this->referralX, 100_000_000, '2026-06-15');
    lendingApplication($this->officerB, $this->referralY, 90_000_000, null)
        ->update(['application_status' => ApplicationStatus::Canceled]);

    expect(LendingQuery::perOfficer(new LendingFilters)->pluck('name')->all())
        ->toBe(['Andi Prasetyo'])
        ->and(LendingQuery::perReferral(new LendingFilters)->pluck('name')->all())
        ->toBe(['Budi Santoso']);
});

it('sums unit_count rather than counting rows', function () {
    lendingApplication($this->officerA, $this->referralX, 100_000_000, '2026-06-15', units: 3);

    expect(LendingQuery::totals(new LendingFilters)->actualUnits)->toBe(3);
});

/* ------------------------------------------------------------ Pengelompokan */

it('groups per officer by account officer names', function () {
    lendingApplication($this->officerA, $this->referralX, 100_000_000, '2026-06-15');
    lendingApplication($this->officerB, $this->referralY, 60_000_000, '2026-06-20');

    $rows = LendingQuery::perOfficer(new LendingFilters);

    expect($rows)->toHaveCount(2)
        ->and($rows->pluck('name')->all())->toContain('Andi Prasetyo', 'Rina Marlina');
});

it('groups per referral by referral names', function () {
    lendingApplication($this->officerA, $this->referralX, 100_000_000, '2026-06-15');
    lendingApplication($this->officerB, $this->referralY, 60_000_000, '2026-06-20');

    $rows = LendingQuery::perReferral(new LendingFilters);

    expect($rows)->toHaveCount(2)
        ->and($rows->pluck('name')->all())->toContain('Budi Santoso', 'Eka Putri');
});

it('omits a party with no applications', function () {
    lendingApplication($this->officerA, $this->referralX, 100_000_000, '2026-06-15');

    // officerB and referralY carry nothing.
    expect(LendingQuery::perOfficer(new LendingFilters)->pluck('name')->all())
        ->toBe(['Andi Prasetyo'])
        ->and(LendingQuery::perReferral(new LendingFilters)->pluck('name')->all())
        ->toBe(['Budi Santoso']);
});

/*
 * The invariant stated in AD-11: both groupings read the same data, so their
 * grand totals must agree. If they ever diverge, one of the joins is wrong.
 */
it('produces identical totals from both groupings', function () {
    lendingApplication($this->officerA, $this->referralX, 100_000_000, '2026-06-15');
    lendingApplication($this->officerA, $this->referralY, 60_000_000, null);
    lendingApplication($this->officerB, $this->referralX, 45_000_000, '2026-07-01', units: 2);
    lendingApplication($this->officerB, $this->referralY, null, null);

    $filters = new LendingFilters;

    $perOfficer = LendingQuery::perOfficer($filters);
    $perReferral = LendingQuery::perReferral($filters);

    $sum = fn ($rows, string $field) => $rows->sum(fn ($row) => $row->{$field});

    foreach (['actualUnits', 'actualAmount', 'pipelineUnits', 'pipelineAmount'] as $field) {
        expect($sum($perOfficer, $field))->toBe(
            $sum($perReferral, $field),
            "Total {$field} berbeda antara pengelompokan Per AO dan Per Referral."
        );
    }

    $totals = LendingQuery::totals($filters);

    expect($totals->actualUnits)->toBe($sum($perOfficer, 'actualUnits'))
        ->and($totals->actualAmount)->toBe($sum($perOfficer, 'actualAmount'))
        ->and($totals->pipelineUnits)->toBe($sum($perOfficer, 'pipelineUnits'))
        ->and($totals->pipelineAmount)->toBe($sum($perOfficer, 'pipelineAmount'));
});

/* ----------------------------------------------------------------- Periode */

/*
 * The rule most easily got wrong: the period cuts Actual only. Pipe Line is a
 * current position and must be unaffected — docs/lending.md section 7.
 */
it('applies the period to actual but never to pipe line', function () {
    lendingApplication($this->officerA, $this->referralX, 100_000_000, '2026-06-15');
    lendingApplication($this->officerA, $this->referralX, 200_000_000, '2026-01-10');
    lendingApplication($this->officerA, $this->referralX, 70_000_000, null);

    $unfiltered = LendingQuery::totals(new LendingFilters);
    expect($unfiltered->actualAmount)->toBe(300_000_000)
        ->and($unfiltered->pipelineAmount)->toBe(70_000_000);

    $june = LendingQuery::totals(new LendingFilters(from: '2026-06-01', to: '2026-06-30'));

    expect($june->actualAmount)->toBe(100_000_000)
        ->and($june->actualUnits)->toBe(1)
        // Pipe Line unchanged by the period.
        ->and($june->pipelineAmount)->toBe(70_000_000)
        ->and($june->pipelineUnits)->toBe(1);
});

it('shows every go live application when no period is chosen', function () {
    lendingApplication($this->officerA, $this->referralX, 100_000_000, '2020-01-01');
    lendingApplication($this->officerA, $this->referralX, 100_000_000, '2026-08-01');

    expect(LendingQuery::totals(new LendingFilters)->actualUnits)->toBe(2);
});

/* --------------------------------------------------------------- Penyaring */

it('filters by financing product', function () {
    lendingApplication($this->officerA, $this->referralX, 100_000_000, '2026-06-15', product: 'DTN');
    lendingApplication($this->officerA, $this->referralX, 200_000_000, '2026-06-16', product: 'UCF');

    $ucf = LendingQuery::totals(new LendingFilters(product: 'UCF'));

    expect($ucf->actualAmount)->toBe(200_000_000)
        ->and($ucf->actualUnits)->toBe(1);
});

it('filters by referral category while grouping stays at account level', function () {
    lendingApplication($this->officerA, $this->referralX, 100_000_000, '2026-06-15');
    lendingApplication($this->officerA, $this->referralY, 200_000_000, '2026-06-16');

    $filtered = LendingQuery::totals(new LendingFilters(categoryId: $this->referralX->category_id));

    expect($filtered->actualAmount)->toBeGreaterThan(0)
        ->and($filtered->actualAmount)->toBeLessThanOrEqual(300_000_000);
});

it('makes the total follow the active filter', function () {
    lendingApplication($this->officerA, $this->referralX, 100_000_000, '2026-06-15', product: 'DTN');
    lendingApplication($this->officerA, $this->referralX, 200_000_000, '2026-06-16', product: 'UCF');

    $filters = new LendingFilters(product: 'DTN');
    $rows = LendingQuery::perOfficer($filters);
    $totals = LendingQuery::totals($filters);

    expect($totals->actualAmount)->toBe($rows->sum(fn ($row) => $row->actualAmount))
        ->and($totals->actualAmount)->toBe(100_000_000);
});

it('ignores a malformed period rather than interpolating it', function () {
    lendingApplication($this->officerA, $this->referralX, 100_000_000, '2026-06-15');

    $filters = LendingFilters::fromArray(['from' => "2026-06-01'; DROP TABLE applications; --"]);

    expect($filters->from)->toBeNull()
        ->and(LendingQuery::totals($filters)->actualAmount)->toBe(100_000_000);
});

it('turns a month filter into a go live date range', function () {
    lendingApplication($this->officerA, $this->referralX, 100_000_000, '2026-06-15');
    lendingApplication($this->officerA, $this->referralX, 200_000_000, '2026-07-01');

    $filters = LendingFilters::fromArray(['month' => '2026-06']);

    expect($filters->month)->toBe('2026-06')
        ->and($filters->from)->toBe('2026-06-01')
        ->and($filters->to)->toBe('2026-06-30')
        ->and(LendingQuery::totals($filters)->actualAmount)->toBe(100_000_000);
});

/* ------------------------------------------------------------------ Akses */

it('shows the lending page to admin with real figures', function () {
    lendingApplication($this->officerA, $this->referralX, 100_000_000, '2026-06-15');

    $this->actingAs(Admin::factory()->create()->user)
        ->get('/lending/ao')
        ->assertOk()
        ->assertSee('Rp 100.000.000')
        ->assertSee('Andi Prasetyo')
        ->assertSee('TOTAL');
});

it('refuses lending to officer and referral', function (string $state) {
    $this->actingAs(User::factory()->{$state}()->create());

    $this->get('/lending')->assertForbidden();
    $this->get('/lending/ao')->assertForbidden();
    $this->get('/lending/referrals')->assertForbidden();
})->with(['referral', 'accountOfficer']);
