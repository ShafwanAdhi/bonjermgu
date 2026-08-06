<?php

namespace Database\Seeders;

use App\Domain\Application\ApplicationCreator;
use App\Domain\Application\DebtorType;
use App\Domain\Application\DocumentReconciler;
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
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Development sample applications for dashboards, lists, and Lending.
 *
 * Idempotent: the same seed rows are updated in place on repeated runs.
 */
class ApplicationSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('ApplicationSeeder dilewati: aplikasi contoh tidak dibuat di production.');

            return;
        }

        $officers = AccountOfficer::query()
            ->with('user')
            ->get()
            ->keyBy(fn (AccountOfficer $officer) => $officer->user->username);
        $referrals = Referral::query()
            ->with('user')
            ->get()
            ->keyBy(fn (Referral $referral) => $referral->user->username);

        if ($officers->isEmpty() || $referrals->isEmpty()) {
            throw new RuntimeException(
                'Akun AO atau Referral belum tersedia. Jalankan UserSeeder terlebih dahulu.'
            );
        }

        if (DB::table('tracking_stages')->count() !== 11 || DB::table('document_requirements')->count() === 0) {
            throw new RuntimeException(
                'Katalog tracking atau dokumen belum tersedia. Jalankan seeder fondasi application terlebih dahulu.'
            );
        }

        DB::transaction(function () use ($officers, $referrals) {
            foreach ($this->scenarios() as $scenario) {
                $this->seedScenario($scenario, $officers, $referrals);
            }
        });

        $this->command?->info('15 aplikasi contoh berhasil disiapkan dengan variasi pipeline dan Go Live.');
    }

    /**
     * @param  array{
     *     officer_username: string,
     *     referral_username: string,
     *     product: FinancingProduct,
     *     debtor_name: string,
     *     debtor_nik: string,
     *     debtor_birth_date: string,
     *     debtor_type: DebtorType,
     *     spouse_income_type: SpouseIncomeType|null,
     *     amount_finance: int|null,
     *     unit_count: int,
     *     completed_documents: int|'all',
     *     completed_stages: int,
     *     go_live_date: string|null
     * }  $scenario
     */
    private function seedScenario(array $scenario, Collection $officers, Collection $referrals): void
    {
        /** @var AccountOfficer|null $officer */
        $officer = $officers->get($scenario['officer_username']);
        /** @var Referral|null $referral */
        $referral = $referrals->get($scenario['referral_username']);

        if (! $officer || ! $referral) {
            throw new RuntimeException(
                "Relasi aplikasi contoh '{$scenario['debtor_name']}' tidak dapat di-resolve."
            );
        }

        $attributes = [
            'account_officer_id' => $officer->id,
            'referral_id' => $referral->id,
            'financing_product' => $scenario['product'],
            'debtor_name' => $scenario['debtor_name'],
            'debtor_nik' => $scenario['debtor_nik'],
            'debtor_birth_date' => $scenario['debtor_birth_date'],
            'debtor_type' => $scenario['debtor_type'],
            'spouse_income_type' => $scenario['spouse_income_type'],
            'amount_finance' => $scenario['amount_finance'],
            'unit_count' => $scenario['unit_count'],
        ];

        $application = Application::withoutGlobalScope(ApplicationVisibilityScope::class)
            ->where('debtor_nik', $scenario['debtor_nik'])
            ->first();

        if (! $application) {
            $application = ApplicationCreator::create($attributes, $officer->user->id);
        } else {
            $application->fill($attributes)->save();
            DocumentReconciler::reconcile($application, $officer->user->id);
        }

        $application = Application::withoutGlobalScope(ApplicationVisibilityScope::class)
            ->with(['documents.requirement', 'trackings'])
            ->find($application->id);

        if (! $application) {
            throw new RuntimeException("Aplikasi contoh '{$scenario['debtor_name']}' gagal dimuat ulang.");
        }

        $this->syncDocumentStatuses(
            $application,
            $scenario['completed_documents'],
            $officer->user->id,
        );
        $this->syncTrackingStatuses(
            $application,
            $scenario['completed_stages'],
            $scenario['go_live_date'],
            $officer->user->id,
        );
    }

    /**
     * @param  int|'all'  $completedDocuments
     */
    private function syncDocumentStatuses(
        Application $application,
        int|string $completedDocuments,
        int $actorId,
    ): void {
        $orderedIds = $application->documents
            ->sortBy(fn (ApplicationDocument $document) => $document->requirement?->sort_order ?? PHP_INT_MAX)
            ->pluck('id')
            ->values();

        $completeCount = $completedDocuments === 'all'
            ? $orderedIds->count()
            : min(max($completedDocuments, 0), $orderedIds->count());

        ApplicationDocument::query()
            ->where('application_id', $application->id)
            ->update([
                'status' => DocumentStatus::Belum->value,
                'updated_by' => null,
            ]);

        if ($completeCount === 0) {
            return;
        }

        ApplicationDocument::query()
            ->whereIn('id', $orderedIds->take($completeCount)->all())
            ->update([
                'status' => DocumentStatus::Lengkap->value,
                'updated_by' => $actorId,
            ]);
    }

    private function syncTrackingStatuses(
        Application $application,
        int $completedStages,
        ?string $goLiveDate,
        int $actorId,
    ): void {
        if ($completedStages < 0 || $completedStages > 11) {
            throw new RuntimeException("Jumlah tracking selesai untuk '{$application->debtor_name}' tidak valid.");
        }

        if ($completedStages === 11 && $goLiveDate === null) {
            $goLiveDate = now()->toDateString();
        }

        if ($completedStages < 11 && $goLiveDate !== null) {
            throw new RuntimeException('Go Live date hanya boleh diisi bila stage 11 sudah selesai.');
        }

        $now = now();
        $rows = collect(range(1, 11))
            ->map(fn (int $stageNo) => [
                'application_id' => $application->id,
                'stage_no' => $stageNo,
                'status' => $stageNo <= $completedStages
                    ? TrackingStatus::Selesai->value
                    : TrackingStatus::Belum->value,
                'updated_by' => $stageNo <= $completedStages ? $actorId : null,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

        ApplicationTracking::query()->upsert(
            $rows,
            ['application_id', 'stage_no'],
            ['status', 'updated_by', 'updated_at'],
        );

        Application::withoutGlobalScope(ApplicationVisibilityScope::class)
            ->whereKey($application->id)
            ->update(['go_live_date' => $goLiveDate]);
    }

    /**
     * @return array<int, array{
     *     officer_username: string,
     *     referral_username: string,
     *     product: FinancingProduct,
     *     debtor_name: string,
     *     debtor_nik: string,
     *     debtor_birth_date: string,
     *     debtor_type: DebtorType,
     *     spouse_income_type: SpouseIncomeType|null,
     *     amount_finance: int|null,
     *     unit_count: int,
     *     completed_documents: int|'all',
     *     completed_stages: int,
     *     go_live_date: string|null
     * }>
     */
    private function scenarios(): array
    {
        return [
            $this->scenario('aorahmawati', 'budisantoso', FinancingProduct::DanaTunai, 'Agus Pratama', '3173990000000001', '1990-01-10', DebtorType::PeroranganNonWiraswasta, SpouseIncomeType::TidakAda, null, 1, 0, 0),
            $this->scenario('aorahmawati', 'budisantoso', FinancingProduct::MobilBekas, 'Rina Marlina', '3173990000000002', '1988-07-23', DebtorType::PeroranganWiraswasta, SpouseIncomeType::Bekerja, 175_000_000, 1, 3, 3),
            $this->scenario('aorahmawati', 'budisantoso', FinancingProduct::DanaTunai, 'CV Sukses Mandiri', '3173990000000003', '1981-02-14', DebtorType::BadanHukumUsaha, null, 240_000_000, 2, 8, 8),
            $this->scenario('aorahmawati', 'sitinurhaliza', FinancingProduct::MobilBekas, 'Yuli Hartono', '3173990000000004', '1993-09-03', DebtorType::PeroranganNonWiraswasta, SpouseIncomeType::Profesional, 125_000_000, 1, 5, 5),
            $this->scenario('aorahmawati', 'sitinurhaliza', FinancingProduct::DanaTunai, 'Dian Kurniasih', '3173990000000005', '1991-12-01', DebtorType::PeroranganWiraswasta, SpouseIncomeType::Usaha, 95_000_000, 1, 6, 7),
            $this->scenario('aorahmawati', 'sitinurhaliza', FinancingProduct::MobilBekas, 'PT Mandala Armada', '3173990000000006', '1979-04-19', DebtorType::BadanHukumUsaha, null, 310_000_000, 2, 'all', 11, '2026-03-14'),
            $this->scenario('aorahmawati', 'dedikurniawan', FinancingProduct::DanaTunai, 'Sari Wulandari', '3173990000000007', '1995-05-28', DebtorType::PeroranganNonWiraswasta, SpouseIncomeType::Bekerja, null, 1, 1, 1),
            $this->scenario('aorahmawati', 'dedikurniawan', FinancingProduct::MobilBekas, 'Fauzan Akbar', '3173990000000008', '1989-11-11', DebtorType::PeroranganWiraswasta, SpouseIncomeType::TidakAda, 210_000_000, 1, 4, 9),
            $this->scenario('aosetiawan', 'budisantoso', FinancingProduct::DanaTunai, 'Lukman Hakim', '3173990000000009', '1987-03-30', DebtorType::PeroranganNonWiraswasta, SpouseIncomeType::Profesional, 88_000_000, 1, 'all', 11, '2025-12-21'),
            $this->scenario('aosetiawan', 'budisantoso', FinancingProduct::MobilBekas, 'PT Cakra Logistik', '3173990000000010', '1978-08-12', DebtorType::BadanHukumUsaha, null, 420_000_000, 2, 9, 10),
            $this->scenario('aosetiawan', 'sitinurhaliza', FinancingProduct::DanaTunai, 'Maya Puspita', '3173990000000011', '1992-06-06', DebtorType::PeroranganWiraswasta, SpouseIncomeType::Bekerja, 132_000_000, 1, 5, 4),
            $this->scenario('aosetiawan', 'sitinurhaliza', FinancingProduct::MobilBekas, 'Robby Firmansyah', '3173990000000012', '1994-10-21', DebtorType::PeroranganNonWiraswasta, SpouseIncomeType::TidakAda, 160_000_000, 1, 'all', 11, '2026-01-28'),
            $this->scenario('aosetiawan', 'dedikurniawan', FinancingProduct::DanaTunai, 'PT Nusantara Jaya', '3173990000000013', '1976-01-27', DebtorType::BadanHukumUsaha, null, 515_000_000, 3, 'all', 11, '2026-06-09'),
            $this->scenario('aosetiawan', 'dedikurniawan', FinancingProduct::MobilBekas, 'Nadia Permata', '3173990000000014', '1996-02-17', DebtorType::PeroranganNonWiraswasta, SpouseIncomeType::Usaha, 190_000_000, 1, 2, 2),
            $this->scenario('aosetiawan', 'dedikurniawan', FinancingProduct::DanaTunai, 'Hendra Saputra', '3173990000000015', '1986-09-09', DebtorType::PeroranganWiraswasta, SpouseIncomeType::Profesional, 145_000_000, 1, 'all', 11, '2026-07-18'),
        ];
    }

    /**
     * @param  int|'all'  $completedDocuments
     * @return array{
     *     officer_username: string,
     *     referral_username: string,
     *     product: FinancingProduct,
     *     debtor_name: string,
     *     debtor_nik: string,
     *     debtor_birth_date: string,
     *     debtor_type: DebtorType,
     *     spouse_income_type: SpouseIncomeType|null,
     *     amount_finance: int|null,
     *     unit_count: int,
     *     completed_documents: int|'all',
     *     completed_stages: int,
     *     go_live_date: string|null
     * }
     */
    private function scenario(
        string $officerUsername,
        string $referralUsername,
        FinancingProduct $product,
        string $debtorName,
        string $debtorNik,
        string $debtorBirthDate,
        DebtorType $debtorType,
        ?SpouseIncomeType $spouseIncomeType,
        ?int $amountFinance,
        int $unitCount,
        int|string $completedDocuments,
        int $completedStages,
        ?string $goLiveDate = null,
    ): array {
        return [
            'officer_username' => $officerUsername,
            'referral_username' => $referralUsername,
            'product' => $product,
            'debtor_name' => $debtorName,
            'debtor_nik' => $debtorNik,
            'debtor_birth_date' => $debtorBirthDate,
            'debtor_type' => $debtorType,
            'spouse_income_type' => $spouseIncomeType,
            'amount_finance' => $amountFinance,
            'unit_count' => $unitCount,
            'completed_documents' => $completedDocuments,
            'completed_stages' => $completedStages,
            'go_live_date' => $goLiveDate,
        ];
    }
}
