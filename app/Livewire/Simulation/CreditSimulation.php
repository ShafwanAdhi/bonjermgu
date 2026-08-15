<?php

namespace App\Livewire\Simulation;

use App\Application\Simulation\DatabaseSimulationInput;
use App\Domain\Simulation\CoverageType;
use App\Domain\Simulation\DebtorType;
use App\Domain\Simulation\FinancingType;
use App\Domain\Simulation\InstalmentType;
use App\Domain\Simulation\Output\SimulationResult;
use App\Domain\Simulation\SimulationMode;
use App\Domain\Simulation\StnkOwnership;
use App\Domain\Simulation\VehicleUsage as DomainVehicleUsage;
use App\Models\AgeGroup;
use App\Models\Domicile;
use App\Models\Referral;
use App\Models\VehiclePrice;
use App\Repositories\MasterLookupRepository;
use App\Repositories\VehicleCascadeRepository;
use App\Services\SimulationService;
use App\Support\RupiahInput;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use RuntimeException;
use Throwable;

final class CreditSimulation extends Component
{
    private const FORM_SESSION_KEY = 'simulation.credit.form';

    private const FORM_STATE_PROPERTIES = [
        'financing_type',
        'mode',
        'domicile_id',
        'debtor_type',
        'age_group_id',
        'usage_id',
        'brand_id',
        'type_id',
        'model_id',
        'vehicle_year',
        'instalment_type',
        'coverage_type',
        'stnk_ownership',
        'funding_purpose',
        'market_price',
        'desired_amount',
    ];

    public string $financing_type = 'DTN';

    public string $mode = 'A';

    public string $debtor_name = '';

    public string $debtor_nik = '';

    public string $debtor_birth_date = '';

    public ?string $domicile_id = null;

    public string $debtor_type = 'non_entrepreneur';

    public ?string $age_group_id = null;

    public ?string $usage_id = null;

    public ?string $brand_id = null;

    public ?string $type_id = null;

    public ?string $model_id = null;

    public ?string $vehicle_year = null;

    public string $instalment_type = 'ADDB';

    public string $coverage_type = 'comprehensive_then_tlo';

    public string $stnk_ownership = 'own';

    public string $funding_purpose = '';

    public string $market_price = '';

    public string $desired_amount = '';

    /** @var array<int, array{tenor: string, disbursement: string, instalment: string, zero: bool}> */
    public array $results = [];

    public bool $hasCalculated = false;

    public bool $priceUnavailable = false;

    public ?string $calculationError = null;

    public bool $showPrintForm = false;

    public ?string $activeProductName = null;

    public ?string $vehicleSummary = null;

    public function mount(): void
    {
        $this->restoreFormState();
        $this->results = $this->zeroRows();
        $this->calculationError = session()->pull('simulation_error');
    }

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        return [
            'financing_type' => ['required', Rule::enum(FinancingType::class)],
            'mode' => ['required', Rule::enum(SimulationMode::class)],
            'domicile_id' => ['required', 'integer', Rule::exists('domiciles', 'id')],
            'debtor_type' => ['required', Rule::enum(DebtorType::class)],
            'age_group_id' => [
                Rule::requiredIf($this->debtor_type !== DebtorType::LEGAL_ENTITY->value),
                'nullable',
                'integer',
                Rule::exists('age_groups', 'id'),
            ],
            'usage_id' => [
                'required',
                'integer',
                Rule::exists('vehicle_usages', 'id'),
                Rule::in($this->usages()->pluck('id')->all()),
            ],
            'brand_id' => [
                'required',
                'integer',
                Rule::exists('vehicle_brands', 'id')->where('usage_id', $this->usage_id),
            ],
            'type_id' => [
                'required',
                'integer',
                Rule::exists('vehicle_types', 'id')->where('brand_id', $this->brand_id),
            ],
            'model_id' => [
                'required',
                'integer',
                Rule::exists('vehicle_models', 'id')->where('type_id', $this->type_id),
            ],
            'vehicle_year' => [
                'required',
                'integer',
                Rule::exists('vehicle_prices', 'year')->where(fn ($query) => $query
                    ->where('model_id', $this->model_id)
                    ->where('price', '>', 0)),
            ],
            'instalment_type' => ['required', Rule::enum(InstalmentType::class)],
            'coverage_type' => ['required', Rule::enum(CoverageType::class)],
            'stnk_ownership' => ['required', Rule::enum(StnkOwnership::class)],
            'funding_purpose' => [
                Rule::requiredIf($this->financing_type === FinancingType::DTN->value),
                'nullable',
                Rule::in(array_keys($this->fundingPurposes())),
            ],
            'market_price' => [
                Rule::requiredIf($this->financing_type === FinancingType::UCF->value),
                'nullable',
                'integer',
                'min:1',
            ],
            'desired_amount' => [
                Rule::requiredIf($this->mode === SimulationMode::B->value),
                'nullable',
                'integer',
                'min:1',
            ],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    protected function printRules(): array
    {
        return [
            'debtor_name' => ['required', 'string', 'max:150'],
            'debtor_nik' => [
                Rule::requiredIf($this->needsPersonalDebtorIdentity()),
                'nullable',
                'digits:16',
            ],
            'debtor_birth_date' => [
                Rule::requiredIf($this->needsPersonalDebtorIdentity()),
                'nullable',
                'date',
                'before:today',
            ],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'financing_type' => 'Jenis Pembiayaan',
            'mode' => 'Dasar Simulasi',
            'debtor_name' => 'Nama',
            'debtor_nik' => 'NIK',
            'debtor_birth_date' => 'Tanggal Lahir',
            'domicile_id' => 'Domisili Debitur',
            'debtor_type' => 'Type Debitur',
            'age_group_id' => 'Usia Debitur',
            'usage_id' => 'Penggunaan Unit',
            'brand_id' => 'Merk',
            'type_id' => 'Type Kendaraan',
            'model_id' => 'Model Kendaraan',
            'vehicle_year' => 'Tahun Kendaraan',
            'instalment_type' => 'Type Angsuran',
            'coverage_type' => 'Asuransi',
            'stnk_ownership' => 'STNK atas nama',
            'funding_purpose' => 'Kebutuhan Dana',
            'market_price' => 'Harga Pasar',
            'desired_amount' => $this->financing_type === FinancingType::DTN->value
                ? 'Dana yang dibutuhkan'
                : 'Total DP dikehendaki',
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'debtor_nik.digits' => 'NIK harus terdiri dari 16 angka.',
            'debtor_birth_date.before' => 'Tanggal Lahir harus sebelum hari ini.',
            'vehicle_year.exists' => 'Harga kendaraan tidak tersedia untuk tahun yang dipilih.',
            'usage_id.in' => 'Penggunaan unit tidak tersedia untuk kategori Referral ini.',
        ];
    }

    #[Computed]
    public function domiciles(): Collection
    {
        return app(MasterLookupRepository::class)->domiciles();
    }

    #[Computed]
    public function ageGroups(): Collection
    {
        return app(MasterLookupRepository::class)->ageGroups();
    }

    #[Computed]
    public function usages(): Collection
    {
        $allowedUsageNames = collect($this->referral()->category->allowedVehicleUsages())
            ->map(fn ($usage) => $usage->value);

        // Pembiayaan Mobil Bekas hanya tersedia untuk unit Passenger, apa pun
        // penggunaan unit yang diizinkan kategori Referral.
        if ($this->financing_type === FinancingType::UCF->value) {
            $allowedUsageNames = $allowedUsageNames
                ->intersect([DomainVehicleUsage::PASSENGER->value]);
        }

        return app(VehicleCascadeRepository::class)
            ->usages()
            ->whereIn('name', $allowedUsageNames)
            ->values();
    }

    #[Computed]
    public function brands(): Collection
    {
        return $this->usage_id
            ? app(VehicleCascadeRepository::class)->brandsForUsage((int) $this->usage_id)
            : collect();
    }

    #[Computed]
    public function vehicleTypes(): Collection
    {
        return $this->brand_id
            ? app(VehicleCascadeRepository::class)->typesForBrand((int) $this->brand_id)
            : collect();
    }

    #[Computed]
    public function vehicleModels(): Collection
    {
        return $this->type_id
            ? app(VehicleCascadeRepository::class)->modelsForType((int) $this->type_id)
            : collect();
    }

    #[Computed]
    public function vehicleYears(): Collection
    {
        return $this->model_id
            ? app(VehicleCascadeRepository::class)->yearsForModel((int) $this->model_id)
            : collect();
    }

    public function updated(string $property): void
    {
        $this->resetValidation($property);

        if (in_array($property, ['debtor_name', 'debtor_nik', 'debtor_birth_date'], true)) {
            return;
        }

        $this->forgetActiveResult();
        $this->priceUnavailable = false;
        $this->calculationError = null;
        $this->showPrintForm = false;

        switch ($property) {
            case 'usage_id':
                $this->resetVehicleAfterUsage();
                break;
            case 'brand_id':
                $this->resetVehicleAfterBrand();
                break;
            case 'type_id':
                $this->resetVehicleAfterType();
                break;
            case 'model_id':
                $this->resetVehicleAfterModel();
                break;
            case 'debtor_type':
                $this->resetAgeGroupForLegalEntity();
                break;
        }

        $this->persistFormState();
        $this->clearResults();
    }

    public function calculate(): void
    {
        $this->normalizeMoneyInputs();
        $this->persistFormState();
        $this->forgetActiveResult();
        $this->clearResults();
        $this->resetValidation();
        $this->priceUnavailable = false;
        $this->calculationError = null;
        $this->showPrintForm = false;

        if ($this->selectedPriceIsUnavailable()) {
            $this->priceUnavailable = true;
            $this->calculationError = 'Harga kendaraan tidak tersedia untuk tahun yang dipilih. Pilih tahun lain.';
            $this->addError('vehicle_year', 'Harga kendaraan tidak tersedia untuk tahun yang dipilih.');

            return;
        }

        $validated = $this->validate();

        try {
            $ageGroup = $validated['age_group_id']
                ? AgeGroup::query()->findOrFail($validated['age_group_id'])->label
                : null;
            $result = app(SimulationService::class)->simulate(
                $this->referral(),
                new DatabaseSimulationInput(
                    vehicleModelId: (int) $validated['model_id'],
                    vehicleYear: (int) $validated['vehicle_year'],
                    financingType: FinancingType::from($validated['financing_type']),
                    mode: SimulationMode::from($validated['mode']),
                    debtorType: DebtorType::from($validated['debtor_type']),
                    ageGroup: $ageGroup,
                    stnkOwnership: StnkOwnership::from($validated['stnk_ownership']),
                    instalmentType: InstalmentType::from($validated['instalment_type']),
                    coverageType: CoverageType::from($validated['coverage_type']),
                    marketPrice: (float) ($validated['market_price'] ?: 0),
                    desiredAmount: (float) ($validated['desired_amount'] ?: 0),
                ),
                (int) today()->format('Y'),
            );

            $this->activateResult($result, $validated, $ageGroup);
            $this->dispatch('simulation-calculated');
        } catch (RuntimeException $exception) {
            $this->calculationError = $exception->getMessage();
        } catch (Throwable $exception) {
            report($exception);
            $this->calculationError = 'Simulasi gagal dihitung. Periksa input atau hubungi Admin.';
        }
    }

    public function clearFormData(): void
    {
        session()->forget(self::FORM_SESSION_KEY);
        $this->resetFormStateProperties();
        $this->resetValidation();
        $this->forgetActiveResult();
        $this->priceUnavailable = false;
        $this->calculationError = null;
        $this->showPrintForm = false;
        $this->activeProductName = null;
        $this->vehicleSummary = null;
        $this->clearResults();
        $this->clearComputedOptions();
    }

    private function normalizeMoneyInputs(): void
    {
        $this->market_price = RupiahInput::normalize($this->market_price);
        $this->desired_amount = RupiahInput::normalize($this->desired_amount);
    }

    public function openPrintForm(): void
    {
        if (! $this->hasCalculated || ! session()->has('simulation.active')) {
            $this->calculationError = 'Hitung simulasi terlebih dahulu sebelum mengunduh.';

            return;
        }

        $this->showPrintForm = true;
        $this->resetValidation(['debtor_name', 'debtor_nik', 'debtor_birth_date']);
    }

    public function closePrintForm(): void
    {
        $this->showPrintForm = false;
        $this->resetValidation(['debtor_name', 'debtor_nik', 'debtor_birth_date']);
    }

    public function preparePrint()
    {
        if (! $this->hasCalculated) {
            $this->calculationError = 'Hitung simulasi terlebih dahulu sebelum mengunduh.';

            return null;
        }

        if (! $this->needsPersonalDebtorIdentity()) {
            $this->reset('debtor_nik', 'debtor_birth_date');
        }

        $validated = $this->validate(
            $this->printRules(),
            $this->messages(),
            $this->validationAttributes(),
        );
        $snapshot = session()->get('simulation.active');

        if (! is_array($snapshot) || ($snapshot['owner_user_id'] ?? null) !== Auth::id()) {
            $this->calculationError = 'Hasil simulasi aktif tidak ditemukan. Hitung ulang simulasi sebelum mengunduh.';
            $this->showPrintForm = false;

            return null;
        }

        $snapshot['subject']['debtor_name'] = trim($validated['debtor_name']);
        $snapshot['subject']['debtor_nik'] = $this->needsPersonalDebtorIdentity()
            ? ($validated['debtor_nik'] ?? null)
            : null;
        $snapshot['subject']['debtor_birth_date'] = $this->needsPersonalDebtorIdentity() && ($validated['debtor_birth_date'] ?? null)
            ? Carbon::parse($validated['debtor_birth_date'])->locale('id')->translatedFormat('d F Y')
            : null;
        session()->put('simulation.active', $snapshot);

        return $this->redirectRoute('simulation.print');
    }

    public function disbursementHeading(): string
    {
        return match ($this->financing_type.'-'.$this->mode) {
            'DTN-A' => 'Pencairan Maksimal',
            'DTN-B' => 'Pencairan',
            'UCF-A' => 'Pencairan Neto',
            'UCF-B' => 'Total DP',
            default => 'Pencairan',
        };
    }

    public function productLabel(): string
    {
        return $this->financing_type === FinancingType::DTN->value
            ? 'Dana Tunai'
            : 'Pembiayaan Mobil Bekas';
    }

    /** @return array<string, array{label: string, description: string}> */
    public function modeOptions(): array
    {
        return [
            SimulationMode::A->value => [
                'label' => 'Berdasarkan Nilai Kendaraan',
                'description' => $this->financing_type === FinancingType::DTN->value
                    ? 'Mengetahui pencairan maksimal dan angsuran dari nilai kendaraan.'
                    : 'Mengetahui pencairan all in dan angsuran dari nilai kendaraan.',
            ],
            SimulationMode::B->value => [
                'label' => $this->financing_type === FinancingType::DTN->value
                    ? 'Berdasarkan Kebutuhan Dana'
                    : 'Berdasarkan Total DP',
                'description' => $this->financing_type === FinancingType::DTN->value
                    ? 'Menghitung angsuran dari dana yang dibutuhkan.'
                    : 'Menghitung angsuran dari total DP yang dikehendaki.',
            ],
        ];
    }

    public function modeLabel(): string
    {
        return $this->modeOptions()[$this->mode]['label'] ?? 'Dasar simulasi tidak dikenal';
    }

    /** @return array<string, string> */
    public function fundingPurposes(): array
    {
        return [
            'education' => 'Pendidikan',
            'business_capital' => 'Modal Usaha',
            'home_renovation' => 'Renovasi Rumah',
            'travel' => 'Liburan atau Wisata Religi',
            'family_event' => 'Pernikahan atau Persalinan',
        ];
    }

    public function render(): View
    {
        return view('livewire.simulation.credit-simulation')
            ->layout('components.layouts.app', ['title' => 'Simulasi Kredit — Kebon Jeruk Multiguna']);
    }

    private function selectedPriceIsUnavailable(): bool
    {
        if (! $this->model_id || ! $this->vehicle_year) {
            return false;
        }

        return ! VehiclePrice::query()
            ->where('model_id', $this->model_id)
            ->where('year', $this->vehicle_year)
            ->where('price', '>', 0)
            ->exists();
    }

    /** @param array<string, mixed> $validated */
    private function activateResult(SimulationResult $result, array $validated, ?string $ageGroup): void
    {
        $vehicle = app(VehicleCascadeRepository::class)->pricedVehicle(
            (int) $validated['model_id'],
            (int) $validated['vehicle_year'],
        );
        $domicile = Domicile::query()->findOrFail($validated['domicile_id']);
        $rows = [];

        foreach ([12, 24, 36, 48, 60] as $tenor) {
            $tenorResult = $result->forTenor($tenor);
            $rows[] = [
                'tenor' => "{$tenor} Bulan",
                'disbursement' => $this->rupiah($tenorResult->outputAmount),
                'instalment' => $this->rupiah($tenorResult->instalment),
                'zero' => $tenorResult->outputAmount == 0 || $tenorResult->instalment === 0,
            ];
        }

        $this->results = $rows;
        $this->hasCalculated = true;
        $this->activeProductName = $result->financingType === FinancingType::DTN
            ? 'Dana Tunai'
            : 'Pembiayaan Mobil Bekas';
        $this->vehicleSummary = trim("{$vehicle->brand} {$vehicle->type} {$vehicle->model}");

        session()->put('simulation.active', [
            'owner_user_id' => Auth::id(),
            'subject' => [
                'referral_code' => sprintf('REF-%04d', $this->referral()->id),
                'referral_name' => $this->referral()->full_name,
                'product' => $this->productLabel(),
                'mode' => $this->modeLabel(),
                'vehicle' => $this->vehicleSummary,
                'vehicle_year' => $vehicle->year,
                'usage' => $vehicle->usage->value,
                'instalment_type' => $validated['instalment_type'],
                'insurance' => $this->coverageLabel($validated['coverage_type']),
                'domicile' => $domicile->name,
                'debtor_type_value' => $validated['debtor_type'],
                'debtor_type' => $this->debtorTypeLabel($validated['debtor_type']),
                'age_group' => $ageGroup,
                'funding_purpose' => $validated['funding_purpose']
                    ? $this->fundingPurposes()[$validated['funding_purpose']]
                    : null,
            ],
            'results' => $rows,
            'disbursement_heading' => $this->disbursementHeading(),
        ]);
    }

    private function referral(): Referral
    {
        return Auth::user()?->referral()->with('category')->firstOrFail()
            ?? throw new RuntimeException('Profil Referral tidak ditemukan.');
    }

    /** @return array<int, array{tenor: string, disbursement: string, instalment: string, zero: bool}> */
    private function zeroRows(): array
    {
        return collect([12, 24, 36, 48, 60])
            ->map(fn (int $tenor) => [
                'tenor' => "{$tenor} Bulan",
                'disbursement' => 'Rp 0',
                'instalment' => 'Rp 0',
                'zero' => true,
            ])->all();
    }

    private function clearResults(): void
    {
        $this->results = $this->zeroRows();
        $this->hasCalculated = false;
        $this->activeProductName = null;
        $this->vehicleSummary = null;
    }

    private function forgetActiveResult(): void
    {
        session()->forget('simulation.active');
    }

    private function restoreFormState(): void
    {
        $state = session()->get(self::FORM_SESSION_KEY, []);

        if (! is_array($state)) {
            return;
        }

        foreach (self::FORM_STATE_PROPERTIES as $property) {
            if (array_key_exists($property, $state)) {
                $this->{$property} = $state[$property];
            }
        }
    }

    private function persistFormState(): void
    {
        session()->put(self::FORM_SESSION_KEY, $this->formState());
    }

    /** @return array<string, mixed> */
    private function formState(): array
    {
        $state = [];

        foreach (self::FORM_STATE_PROPERTIES as $property) {
            $state[$property] = $this->{$property};
        }

        return $state;
    }

    private function resetFormStateProperties(): void
    {
        $this->financing_type = 'DTN';
        $this->mode = 'A';
        $this->domicile_id = null;
        $this->debtor_type = 'non_entrepreneur';
        $this->age_group_id = null;
        $this->usage_id = null;
        $this->brand_id = null;
        $this->type_id = null;
        $this->model_id = null;
        $this->vehicle_year = null;
        $this->instalment_type = 'ADDB';
        $this->coverage_type = 'comprehensive_then_tlo';
        $this->stnk_ownership = 'own';
        $this->funding_purpose = '';
        $this->market_price = '';
        $this->desired_amount = '';
        $this->debtor_name = '';
        $this->debtor_nik = '';
        $this->debtor_birth_date = '';
    }

    private function clearComputedOptions(): void
    {
        unset($this->brands, $this->vehicleTypes, $this->vehicleModels, $this->vehicleYears);
    }

    private function resetVehicleAfterUsage(): void
    {
        $this->reset('brand_id', 'type_id', 'model_id', 'vehicle_year');
        unset($this->brands, $this->vehicleTypes, $this->vehicleModels, $this->vehicleYears);
    }

    private function resetVehicleAfterBrand(): void
    {
        $this->reset('type_id', 'model_id', 'vehicle_year');
        unset($this->vehicleTypes, $this->vehicleModels, $this->vehicleYears);
    }

    private function resetVehicleAfterType(): void
    {
        $this->reset('model_id', 'vehicle_year');
        unset($this->vehicleModels, $this->vehicleYears);
    }

    private function resetVehicleAfterModel(): void
    {
        $this->reset('vehicle_year');
        unset($this->vehicleYears);
    }

    private function resetAgeGroupForLegalEntity(): void
    {
        if ($this->debtor_type === DebtorType::LEGAL_ENTITY->value) {
            $this->reset('age_group_id', 'debtor_nik', 'debtor_birth_date');
        }
    }

    private function needsPersonalDebtorIdentity(): bool
    {
        return $this->debtor_type !== DebtorType::LEGAL_ENTITY->value;
    }

    private function rupiah(int|float $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    private function coverageLabel(string $coverage): string
    {
        return match ($coverage) {
            CoverageType::COMPREHENSIVE_ALL->value => 'Comprehensive All Tenor',
            CoverageType::COMPREHENSIVE_THEN_TLO->value => 'Comprehensive 1 tahun, sisanya TLO',
            CoverageType::TLO_ALL->value => 'TLO All Tenor',
            default => $coverage,
        };
    }

    private function debtorTypeLabel(string $debtorType): string
    {
        return match ($debtorType) {
            DebtorType::ENTREPRENEUR->value => 'Perorangan Wiraswasta',
            DebtorType::NON_ENTREPRENEUR->value => 'Perorangan Non Wiraswasta',
            DebtorType::LEGAL_ENTITY->value => 'Badan Hukum Usaha',
            default => $debtorType,
        };
    }
}
