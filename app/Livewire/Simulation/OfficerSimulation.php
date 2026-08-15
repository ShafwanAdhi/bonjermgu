<?php

namespace App\Livewire\Simulation;

use App\Application\Simulation\CalculationTrace;
use App\Application\Simulation\ConfigurationSimulationOutcome;
use App\Application\Simulation\OfficerSimulationRequest;
use App\Application\Simulation\OfficerSimulator;
use App\Domain\Simulation\CoverageType;
use App\Domain\Simulation\DebtorType;
use App\Domain\Simulation\FinancingType;
use App\Domain\Simulation\InstalmentType;
use App\Domain\Simulation\SimulationMode;
use App\Domain\Simulation\StnkOwnership;
use App\Domain\Simulation\VehicleUsage;
use App\Models\AgeGroup;
use App\Models\ReferralCategory;
use App\Models\ReferralSubCategory;
use App\Repositories\MasterLookupRepository;
use App\Repositories\VehicleCascadeRepository;
use App\Support\Format;
use App\Support\RupiahInput;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use RuntimeException;
use Throwable;

/**
 * Simulasi Kredit — Account Officer.
 *
 * Runs the same engine as the Referral screen under the Officer profile. Three
 * deliberate differences, all agreed with the client on 11 August 2026:
 *
 *   1. The Referral category is chosen by hand. An Officer has no category, so
 *      it names the one the application came through and the Product resolves
 *      from the same mapping a Referral would hit.
 *   2. Upping, insurance extras, and the disbursement deductions are editable
 *      for this one run. An Officer works from the deal in front of them.
 *   3. Nothing is printed or stored, so no debtor identity is collected.
 */
final class OfficerSimulation extends Component
{
    private const FORM_SESSION_KEY = 'simulation.officer.form';

    private const FORM_STATE_PROPERTIES = [
        'referral_category_id',
        'referral_sub_category_id',
        'financing_type',
        'mode',
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
        'unit_price',
        'desired_amount',
        'rate_variant',
        'up_rate',
        'up_admin',
        'up_provisi',
        'up_acp',
        'ext_flood',
        'ext_earthquake',
        'ext_riot',
        'ext_terrorism',
        'tjh_amount',
        'driver_amount',
        'passenger_amount',
        'passenger_count',
        'engine_warranty',
        'deposit_instalment',
        'bbnkb_amount',
        'pkb_amount',
        'invoice_amount',
    ];

    public ?string $referral_category_id = null;

    public ?string $referral_sub_category_id = null;

    public string $financing_type = 'DTN';

    public string $mode = 'A';

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

    /** Harga Taksasi on Dana Tunai, Harga Pasar on Pembiayaan Mobil Bekas. */
    public string $unit_price = '';

    public string $desired_amount = '';

    public string $rate_variant = 'Batas Bawah';

    public string $up_rate = '0';

    public string $up_admin = '0';

    public string $up_provisi = '0';

    public string $up_acp = '';

    public bool $ext_flood = false;

    public bool $ext_earthquake = false;

    public bool $ext_riot = false;

    public bool $ext_terrorism = false;

    public string $tjh_amount = '0';

    public string $driver_amount = '0';

    public string $passenger_amount = '0';

    public string $passenger_count = '0';

    public bool $engine_warranty = true;

    public string $deposit_instalment = '0';

    public string $bbnkb_amount = '0';

    public string $pkb_amount = '0';

    public string $invoice_amount = '0';

    /** Tenor whose derivation is expanded below the results. */
    public int $traced_tenor = 12;

    public bool $hasCalculated = false;

    public ?string $calculationError = null;

    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    /** @var array<string, string>|null */
    public ?array $summary = null;

    /** @var array<int, array<int, array<string, mixed>>> */
    public array $traces = [];

    private ?ConfigurationSimulationOutcome $outcome = null;

    public function mount(): void
    {
        $this->referral_category_id = $this->defaultReferralCategoryId();
        $this->restoreFormState();
    }

    /* ------------------------------------------------------------ Pilihan */

    #[Computed]
    public function categories(): Collection
    {
        return ReferralCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'segment', 'tier', 'allows_passenger', 'allows_commercial']);
    }

    #[Computed]
    public function subCategories(): Collection
    {
        return $this->referral_category_id
            ? ReferralSubCategory::query()
                ->where('category_id', $this->referral_category_id)
                ->orderBy('name')
                ->get(['id', 'name'])
            : collect();
    }

    #[Computed]
    public function selectedCategory(): ?ReferralCategory
    {
        return $this->referral_category_id
            ? $this->categories->firstWhere('id', (int) $this->referral_category_id)
            : null;
    }

    #[Computed]
    public function ageGroups(): Collection
    {
        return app(MasterLookupRepository::class)->ageGroups();
    }

    /**
     * Penggunaan Unit the chosen category may finance. Pembiayaan Mobil Bekas
     * narrows that to Passenger regardless, per credit-simulation.md section 14b.
     */
    #[Computed]
    public function usages(): Collection
    {
        $category = $this->selectedCategory;

        if (! $category) {
            return collect();
        }

        $allowed = collect($category->allowedVehicleUsages())->map(fn ($usage) => $usage->value);

        if ($this->financing_type === FinancingType::UCF->value) {
            $allowed = $allowed->intersect([VehicleUsage::PASSENGER->value]);
        }

        return app(VehicleCascadeRepository::class)
            ->usages()
            ->whereIn('name', $allowed)
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

    #[Computed]
    public function isUcf(): bool
    {
        return $this->financing_type === FinancingType::UCF->value;
    }

    #[Computed]
    public function isModeB(): bool
    {
        return $this->mode === SimulationMode::B->value;
    }

    /**
     * Handoff into Buat Credit Application, carrying the only two fields the
     * two screens share. Amount Finance and the Referral account are
     * deliberately absent — see CreateApplication's docblock.
     */
    public function createApplicationUrl(): ?string
    {
        if (! $this->hasCalculated) {
            return null;
        }

        return route('applications.create', [
            'financing_product' => $this->financing_type,
            'debtor_type' => $this->debtor_type,
        ]);
    }

    /* ------------------------------------------------------------ Validasi */

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        return [
            'referral_category_id' => ['required', 'integer', Rule::exists('referral_categories', 'id')],
            'referral_sub_category_id' => [
                'nullable',
                'integer',
                Rule::exists('referral_sub_categories', 'id')->where('category_id', $this->referral_category_id),
            ],
            'financing_type' => ['required', Rule::enum(FinancingType::class)],
            'mode' => ['required', Rule::enum(SimulationMode::class)],
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
                Rule::in($this->usages->pluck('id')->all()),
            ],
            'brand_id' => ['required', 'integer', Rule::exists('vehicle_brands', 'id')->where('usage_id', $this->usage_id)],
            'type_id' => ['required', 'integer', Rule::exists('vehicle_types', 'id')->where('brand_id', $this->brand_id)],
            'model_id' => ['required', 'integer', Rule::exists('vehicle_models', 'id')->where('type_id', $this->type_id)],
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
            'rate_variant' => ['required', Rule::in(['Batas Bawah', 'Batas Atas'])],
            // Required on both products: the Officer profile prices Dana Tunai
            // from the appraised value, not from PHPM.
            'unit_price' => ['required', 'integer', 'min:1'],
            'desired_amount' => [Rule::requiredIf($this->isModeB), 'nullable', 'integer', 'min:1'],
            'up_rate' => ['required', 'numeric', 'between:0,100'],
            'up_admin' => ['required', 'integer', 'min:0'],
            'up_provisi' => ['required', 'numeric', 'between:0,100'],
            'up_acp' => ['nullable', 'numeric', 'between:0,100'],
            'tjh_amount' => ['required', 'integer', 'min:0'],
            'driver_amount' => ['required', 'integer', 'min:0'],
            'passenger_amount' => ['required', 'integer', 'min:0'],
            'passenger_count' => ['required', 'integer', 'min:0', 'max:20'],
            'deposit_instalment' => ['required', 'integer', 'min:0', 'max:10'],
            'bbnkb_amount' => ['required', 'integer', 'min:0'],
            'pkb_amount' => ['required', 'integer', 'min:0'],
            'invoice_amount' => ['required', 'integer', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'referral_category_id' => 'Kategori Referral',
            'referral_sub_category_id' => 'Sub Kategori Referral',
            'financing_type' => 'Jenis Pembiayaan',
            'mode' => 'Dasar Simulasi',
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
            'rate_variant' => 'Varian Rate Asuransi',
            'unit_price' => $this->isUcf ? 'Harga Pasar' : 'Harga Taksasi',
            'desired_amount' => $this->isUcf ? 'Total DP dikehendaki' : 'Dana yang dibutuhkan',
            'up_rate' => 'Up Rate',
            'up_admin' => 'Up Admin',
            'up_provisi' => 'Up Provisi',
            'up_acp' => 'Up ACP',
            'tjh_amount' => 'Nilai TJH',
            'driver_amount' => 'Pertanggungan Pengemudi',
            'passenger_amount' => 'Pertanggungan Penumpang',
            'passenger_count' => 'Jumlah Penumpang',
            'deposit_instalment' => 'Deposit Angsuran',
            'bbnkb_amount' => 'BBNKB',
            'pkb_amount' => 'PKB',
            'invoice_amount' => 'Faktur',
        ];
    }

    /* -------------------------------------------------------- Interaksi */

    public function updated(string $property): void
    {
        // Reset whatever sits below the changed field, so a stale child cannot
        // survive a parent change.
        match ($property) {
            'referral_category_id' => $this->reset(
                'referral_sub_category_id', 'usage_id', 'brand_id', 'type_id', 'model_id', 'vehicle_year',
            ),
            'financing_type' => $this->reset('usage_id', 'brand_id', 'type_id', 'model_id', 'vehicle_year'),
            'usage_id' => $this->reset('brand_id', 'type_id', 'model_id', 'vehicle_year'),
            'brand_id' => $this->reset('type_id', 'model_id', 'vehicle_year'),
            'type_id' => $this->reset('model_id', 'vehicle_year'),
            'model_id' => $this->reset('vehicle_year'),
            default => null,
        };

        if ($this->debtor_type === DebtorType::LEGAL_ENTITY->value) {
            $this->reset('age_group_id');
        }

        if (in_array($property, ['referral_category_id', 'financing_type', 'usage_id', 'brand_id', 'type_id', 'model_id'], true)) {
            unset($this->selectedCategory, $this->subCategories, $this->usages, $this->brands, $this->vehicleTypes, $this->vehicleModels, $this->vehicleYears);
        }

        $this->hasCalculated = false;
        $this->calculationError = null;
        $this->rows = [];
        $this->summary = null;
        $this->traces = [];
        $this->persistFormState();
    }

    public function traceTenor(int $tenor): void
    {
        $this->traced_tenor = $tenor;
    }

    public function calculate(): void
    {
        foreach ([
            'unit_price', 'desired_amount', 'up_admin', 'tjh_amount', 'driver_amount',
            'passenger_amount', 'bbnkb_amount', 'pkb_amount', 'invoice_amount',
        ] as $field) {
            $this->{$field} = RupiahInput::normalize($this->{$field});
        }

        $this->persistFormState();
        $this->calculationError = null;
        $this->hasCalculated = false;
        $this->rows = [];
        $this->summary = null;
        $this->traces = [];

        $validated = $this->validate();

        try {
            $outcome = app(OfficerSimulator::class)->run(
                new OfficerSimulationRequest(
                    referralCategoryId: (int) $validated['referral_category_id'],
                    vehicleModelId: (int) $validated['model_id'],
                    vehicleYear: (int) $validated['vehicle_year'],
                    financingType: FinancingType::from($validated['financing_type']),
                    mode: SimulationMode::from($validated['mode']),
                    debtorType: DebtorType::from($validated['debtor_type']),
                    ageGroup: $validated['age_group_id']
                        ? AgeGroup::query()->findOrFail($validated['age_group_id'])->label
                        : null,
                    stnkOwnership: StnkOwnership::from($validated['stnk_ownership']),
                    instalmentType: InstalmentType::from($validated['instalment_type']),
                    coverageType: CoverageType::from($validated['coverage_type']),
                    marketPrice: (float) $validated['unit_price'],
                    desiredAmount: (float) ($validated['desired_amount'] ?: 0),
                    rateVariant: $validated['rate_variant'],
                    upRate: $this->fraction($validated['up_rate']),
                    upAdmin: (float) $validated['up_admin'],
                    upProvision: $this->fraction($validated['up_provisi']),
                    acpUpping: $validated['up_acp'] === '' || $validated['up_acp'] === null
                        ? null
                        : $this->fraction($validated['up_acp']),
                    extensions: [
                        'flood' => $this->ext_flood,
                        'earthquake' => $this->ext_earthquake,
                        'riot' => $this->ext_riot,
                        'terrorism' => $this->ext_terrorism,
                    ],
                    tjhAmount: (float) $validated['tjh_amount'],
                    driverCoverageAmount: (float) $validated['driver_amount'],
                    passengerCoverageAmount: (float) $validated['passenger_amount'],
                    passengerCount: (int) $validated['passenger_count'],
                    engineWarrantyEnabled: $this->engine_warranty,
                    depositInstalmentCount: (int) $validated['deposit_instalment'],
                    bbnkbAmount: (float) $validated['bbnkb_amount'],
                    pkbAmount: (float) $validated['pkb_amount'],
                    invoiceAmount: (float) $validated['invoice_amount'],
                ),
                (int) today()->format('Y'),
            );

            $this->outcome = $outcome;
            $this->rows = collect([12, 24, 36, 48, 60])
                ->map(function (int $tenor) use ($outcome) {
                    $result = $outcome->result->forTenor($tenor);

                    return [
                        'tenor' => $tenor,
                        'label' => Format::tenor($tenor),
                        'disbursement' => Format::rupiah((int) round($result->outputAmount)),
                        'instalment' => Format::rupiah($result->instalment),
                        'zero' => $result->instalment === 0,
                        'reason' => Format::zeroReasonLabel($result->zeroReason),
                    ];
                })->all();

            $this->summary = [
                'product' => $outcome->config->product->name,
                'vehicle' => $outcome->vehicleLabel.' '.$outcome->input->vehicleYear,
                'phpm' => Format::rupiah((int) round($outcome->input->phpmPrice)),
            ];
            $this->traces = collect([12, 24, 36, 48, 60])
                ->mapWithKeys(fn (int $tenor) => [
                    $tenor => CalculationTrace::build($outcome->result->forTenor($tenor), $outcome),
                ])
                ->all();
            $this->hasCalculated = true;
            $this->dispatch('simulation-calculated');
        } catch (RuntimeException $exception) {
            $this->calculationError = $exception->getMessage();
            $this->dispatch('simulation-calculated');
        } catch (Throwable $exception) {
            report($exception);
            $this->calculationError = 'Simulasi gagal dihitung. Periksa parameter dan master kendaraan.';
            $this->dispatch('simulation-calculated');
        }
    }

    public function clearFormData(): void
    {
        session()->forget(self::FORM_SESSION_KEY);
        $this->resetFormStateProperties();
        $this->resetValidation();
        $this->calculationError = null;
        $this->hasCalculated = false;
        $this->rows = [];
        $this->summary = null;
        $this->traces = [];
        $this->outcome = null;
        $this->clearComputedOptions();
    }

    /* ------------------------------------------------------------- Jejak */

    /**
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function trace(): array
    {
        return $this->hasCalculated
            ? ($this->traces[$this->traced_tenor] ?? [])
            : [];
    }

    public function disbursementHeading(): string
    {
        return match (true) {
            ! $this->isUcf && ! $this->isModeB => 'Pencairan Maksimal',
            ! $this->isUcf => 'Pencairan',
            ! $this->isModeB => 'Pencairan Neto',
            default => 'Total DP',
        };
    }

    private function fraction(string|int|float|null $percent): float
    {
        return ((float) $percent) / 100;
    }

    private function defaultReferralCategoryId(): string
    {
        return (string) (ReferralCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->value('id') ?? '');
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
        $this->referral_category_id = $this->defaultReferralCategoryId();
        $this->referral_sub_category_id = null;
        $this->financing_type = 'DTN';
        $this->mode = 'A';
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
        $this->unit_price = '';
        $this->desired_amount = '';
        $this->rate_variant = 'Batas Bawah';
        $this->up_rate = '0';
        $this->up_admin = '0';
        $this->up_provisi = '0';
        $this->up_acp = '';
        $this->ext_flood = false;
        $this->ext_earthquake = false;
        $this->ext_riot = false;
        $this->ext_terrorism = false;
        $this->tjh_amount = '0';
        $this->driver_amount = '0';
        $this->passenger_amount = '0';
        $this->passenger_count = '0';
        $this->engine_warranty = true;
        $this->deposit_instalment = '0';
        $this->bbnkb_amount = '0';
        $this->pkb_amount = '0';
        $this->invoice_amount = '0';
        $this->traced_tenor = 12;
    }

    private function clearComputedOptions(): void
    {
        unset(
            $this->categories,
            $this->subCategories,
            $this->selectedCategory,
            $this->ageGroups,
            $this->usages,
            $this->brands,
            $this->vehicleTypes,
            $this->vehicleModels,
            $this->vehicleYears,
            $this->isUcf,
            $this->isModeB,
            $this->trace,
        );
    }

    public function render(): View
    {
        return view('livewire.simulation.officer-simulation');
    }
}
