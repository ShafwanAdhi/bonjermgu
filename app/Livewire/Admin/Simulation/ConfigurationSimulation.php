<?php

namespace App\Livewire\Admin\Simulation;

use App\Application\Simulation\CalculationTrace;
use App\Application\Simulation\ConfigurationSimulationOutcome;
use App\Application\Simulation\ConfigurationSimulationRequest;
use App\Application\Simulation\ConfigurationSimulator;
use App\Domain\Simulation\CoverageType;
use App\Domain\Simulation\DebtorType;
use App\Domain\Simulation\FinancingType;
use App\Domain\Simulation\InstalmentType;
use App\Domain\Simulation\SimulationMode;
use App\Domain\Simulation\SimulationProfile;
use App\Domain\Simulation\StnkOwnership;
use App\Domain\Simulation\VehicleUsage;
use App\Models\AgeGroup;
use App\Models\Product;
use App\Models\ReferralCategory;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehiclePrice;
use App\Models\VehicleType;
use App\Models\VehicleUsage as VehicleUsageModel;
use App\Repositories\ProductResolver;
use App\Support\Format;
use App\Support\RupiahInput;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use RuntimeException;
use Throwable;

/**
 * Uji Konfigurasi — Admin runs the engine against a Product to see what the
 * configuration they just edited actually produces, and how each figure was
 * derived.
 *
 * Not the Referral simulation. Three deliberate differences:
 *
 *   1. No debtor fields. A configuration check has no debtor, and Admin has no
 *      business touching debtor data (business.md section 5).
 *   2. Product is chosen directly, not resolved from a referral category —
 *      the Product is what is being tested.
 *   3. Nothing is printed or stored. There is no output to hand anyone.
 *
 * The arithmetic runs in the same engine as production. If this screen and a
 * Referral's simulation ever disagree for the same Product and inputs, the bug
 * is real and not a display artefact.
 */
#[Layout('components.layouts.app')]
class ConfigurationSimulation extends Component
{
    private const FORM_SESSION_KEY = 'simulation.configuration.form';

    private const FORM_STATE_PROPERTIES = [
        'product_id',
        'financing_type',
        'simulation_profile',
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
        'market_price',
        'up_rate',
        'up_admin',
        'up_provisi',
        'up_acp',
        'desired_amount',
    ];

    public ?string $product_id = null;

    public string $financing_type = 'UCF';

    /** Which screen's rules to verify — Referral or Account Officer. */
    public string $simulation_profile = 'referral';

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

    public string $market_price = '';

    public string $up_rate = '0';

    public string $up_admin = '0';

    public string $up_provisi = '0';

    public string $up_acp = '';

    public string $desired_amount = '';

    /** Tenor whose derivation is expanded below the results. */
    public int $traced_tenor = 12;

    public bool $hasCalculated = false;

    public ?string $calculationError = null;

    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    /** @var array<int, array<int, array<string, mixed>>> */
    public array $traces = [];

    private ?ConfigurationSimulationOutcome $outcome = null;

    public function mount(): void
    {
        $this->product_id = $this->defaultProductId();
        $this->usage_id = $this->defaultUsageId();
        $this->restoreFormState();
    }

    /* ------------------------------------------------------------ Pilihan */

    #[Computed]
    public function products(): Collection
    {
        return Product::where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function ageGroups(): Collection
    {
        return AgeGroup::orderBy('sort_order')->get(['id', 'label']);
    }

    #[Computed]
    public function usages(): Collection
    {
        $usages = VehicleUsageModel::orderBy('id')->get(['id', 'name']);

        // Pembiayaan Mobil Bekas is Passenger-only, so Commercial is not an
        // option to verify — the engine would refuse it.
        return $this->isUcf
            ? $usages->where('name', VehicleUsage::PASSENGER->value)->values()
            : $usages;
    }

    #[Computed]
    public function brands(): Collection
    {
        return $this->usage_id
            ? VehicleBrand::where('usage_id', $this->usage_id)->orderBy('name')->get(['id', 'name'])
            : collect();
    }

    #[Computed]
    public function vehicleTypes(): Collection
    {
        return $this->brand_id
            ? VehicleType::where('brand_id', $this->brand_id)->orderBy('name')->get(['id', 'name'])
            : collect();
    }

    #[Computed]
    public function vehicleModels(): Collection
    {
        return $this->type_id
            ? VehicleModel::where('type_id', $this->type_id)->orderBy('name')->get(['id', 'name'])
            : collect();
    }

    /** Only years that actually carry a price — the rest guarantee a zero. */
    #[Computed]
    public function vehicleYears(): Collection
    {
        return $this->model_id
            ? VehiclePrice::where('model_id', $this->model_id)
                ->where('price', '>', 0)
                ->orderByDesc('year')
                ->pluck('year')
            : collect();
    }

    /**
     * Which referral categories actually reach the selected Product. An empty
     * answer means no Referral can ever produce these figures in production.
     */
    #[Computed]
    public function reachingCategories(): Collection
    {
        $product = $this->selectedProduct;

        if (! $product) {
            return collect();
        }

        $resolver = app(ProductResolver::class);

        return ReferralCategory::where('is_active', true)->get()
            ->flatMap(function (ReferralCategory $category) use ($resolver, $product) {
                $usages = array_filter([
                    $category->allows_passenger ? VehicleUsage::PASSENGER : null,
                    $category->allows_commercial ? VehicleUsage::COMMERCIAL : null,
                ]);

                return collect($usages)
                    ->filter(fn (VehicleUsage $usage) => $resolver->nameFor($category, $usage) === $product->name)
                    ->map(fn (VehicleUsage $usage) => $category->name.' · '.$usage->value);
            })
            ->values();
    }

    #[Computed]
    public function selectedProduct(): ?Product
    {
        return $this->product_id
            ? Product::with('rates')->find($this->product_id)
            : null;
    }

    /** The rate table of the selected Product — empty means tenor unavailable. */
    #[Computed]
    public function productRates(): array
    {
        $product = $this->selectedProduct;

        if (! $product) {
            return [];
        }

        return collect([12, 24, 36, 48, 60])
            ->mapWithKeys(fn (int $tenor) => [
                $tenor => $product->rates->firstWhere('tenor_months', $tenor)?->effective_rate,
            ])->all();
    }

    #[Computed]
    public function isUcf(): bool
    {
        return $this->financing_type === 'UCF';
    }

    #[Computed]
    public function isModeB(): bool
    {
        return $this->mode === 'B';
    }

    #[Computed]
    public function isOfficer(): bool
    {
        return $this->simulation_profile === SimulationProfile::OFFICER->value;
    }

    /** The Officer profile prices Dana Tunai from an appraised value too. */
    #[Computed]
    public function needsUnitPrice(): bool
    {
        return $this->isUcf || $this->isOfficer;
    }

    public function unitPriceLabel(): string
    {
        return $this->isUcf ? 'Harga Pasar' : 'Harga Taksasi';
    }

    /* ------------------------------------------------------------ Validasi */

    protected function rules(): array
    {
        return [
            'product_id' => ['required', Rule::exists('products', 'id')],
            'financing_type' => ['required', Rule::in(['DTN', 'UCF'])],
            'simulation_profile' => ['required', Rule::enum(SimulationProfile::class)],
            'mode' => ['required', Rule::in(['A', 'B'])],
            'debtor_type' => ['required', Rule::enum(DebtorType::class)],
            'age_group_id' => ['nullable', Rule::exists('age_groups', 'id')],
            'model_id' => ['required', Rule::exists('vehicle_models', 'id')],
            'vehicle_year' => ['required', 'integer'],
            'instalment_type' => ['required', Rule::enum(InstalmentType::class)],
            'coverage_type' => ['required', Rule::enum(CoverageType::class)],
            'stnk_ownership' => ['required', Rule::enum(StnkOwnership::class)],
            'market_price' => [$this->needsUnitPrice ? 'required' : 'nullable', 'numeric', 'min:0'],
            'desired_amount' => [$this->isModeB ? 'required' : 'nullable', 'numeric', 'min:0'],
            'up_rate' => ['required', 'numeric', 'between:0,100'],
            'up_admin' => ['required', 'numeric', 'min:0'],
            'up_provisi' => ['required', 'numeric', 'between:0,100'],
            'up_acp' => ['nullable', 'numeric', 'between:0,100'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'product_id' => 'Product',
            'financing_type' => 'Produk Pembiayaan',
            'simulation_profile' => 'Profil Simulasi',
            'mode' => 'Mode',
            'debtor_type' => 'Type Debitur',
            'age_group_id' => 'Kelompok Usia',
            'model_id' => 'Model Kendaraan',
            'vehicle_year' => 'Tahun Kendaraan',
            'instalment_type' => 'Type Angsuran',
            'coverage_type' => 'Asuransi',
            'stnk_ownership' => 'STNK atas nama',
            'market_price' => $this->unitPriceLabel(),
            'desired_amount' => 'Nominal Dikehendaki',
            'up_rate' => 'Up Rate',
            'up_admin' => 'Up Admin',
            'up_provisi' => 'Up Provisi',
            'up_acp' => 'Up ACP',
        ];
    }

    /* -------------------------------------------------------- Interaksi */

    public function updated(string $property): void
    {
        // Reset the cascade below whatever changed, so a stale child cannot
        // survive a parent change.
        match ($property) {
            // Switching product can narrow the usages on offer, so a Commercial
            // selection must not survive the move to Pembiayaan Mobil Bekas.
            'financing_type' => $this->reset('usage_id', 'brand_id', 'type_id', 'model_id', 'vehicle_year'),
            'usage_id' => $this->reset('brand_id', 'type_id', 'model_id', 'vehicle_year'),
            'brand_id' => $this->reset('type_id', 'model_id', 'vehicle_year'),
            'type_id' => $this->reset('model_id', 'vehicle_year'),
            'model_id' => $this->reset('vehicle_year'),
            default => null,
        };

        if (in_array($property, ['financing_type', 'usage_id', 'brand_id', 'type_id', 'model_id'], true)) {
            unset($this->usages, $this->brands, $this->vehicleTypes, $this->vehicleModels, $this->vehicleYears);
        }

        if ($property === 'simulation_profile') {
            unset($this->isOfficer, $this->needsUnitPrice);
        }

        if ($property === 'product_id') {
            unset($this->selectedProduct, $this->productRates, $this->reachingCategories);
        }

        $this->hasCalculated = false;
        $this->calculationError = null;
        $this->rows = [];
        $this->traces = [];
        $this->persistFormState();
    }

    public function traceTenor(int $tenor): void
    {
        $this->traced_tenor = $tenor;
    }

    public function calculate(): void
    {
        $this->market_price = RupiahInput::normalize($this->market_price);
        $this->desired_amount = RupiahInput::normalize($this->desired_amount);
        $this->up_admin = RupiahInput::normalize($this->up_admin);

        $this->persistFormState();
        $this->calculationError = null;
        $this->hasCalculated = false;
        $this->rows = [];
        $this->traces = [];

        $validated = $this->validate();

        try {
            $outcome = app(ConfigurationSimulator::class)->run(
                new ConfigurationSimulationRequest(
                    product: Product::findOrFail($validated['product_id']),
                    vehicleModelId: (int) $validated['model_id'],
                    vehicleYear: (int) $validated['vehicle_year'],
                    financingType: FinancingType::from($validated['financing_type']),
                    mode: SimulationMode::from($validated['mode']),
                    debtorType: DebtorType::from($validated['debtor_type']),
                    ageGroup: $validated['age_group_id']
                        ? AgeGroup::findOrFail($validated['age_group_id'])->label
                        : null,
                    stnkOwnership: StnkOwnership::from($validated['stnk_ownership']),
                    instalmentType: InstalmentType::from($validated['instalment_type']),
                    coverageType: CoverageType::from($validated['coverage_type']),
                    marketPrice: (float) ($validated['market_price'] ?: 0),
                    desiredAmount: (float) ($validated['desired_amount'] ?: 0),
                    profile: SimulationProfile::from($validated['simulation_profile']),
                    upRate: ((float) $validated['up_rate']) / 100,
                    upAdmin: (float) $validated['up_admin'],
                    upProvision: ((float) $validated['up_provisi']) / 100,
                    acpUpping: ($validated['up_acp'] ?? '') === '' || $validated['up_acp'] === null
                        ? null
                        : ((float) $validated['up_acp']) / 100,
                ),
                (int) today()->format('Y'),
            );

            $this->outcome = $outcome;
            $this->rows = collect([12, 24, 36, 48, 60])
                ->map(function (int $tenor) use ($outcome) {
                    $r = $outcome->result->forTenor($tenor);

                    return [
                        'tenor' => $tenor,
                        'label' => Format::tenor($tenor),
                        'disbursement' => Format::rupiah((int) round($r->outputAmount)),
                        'instalment' => Format::rupiah($r->instalment),
                        'zero' => $r->instalment === 0,
                        'reason' => match (true) {
                            ! $r->rateAvailable => 'Rate kosong',
                            ! $r->eligible => 'Usia unit',
                            default => null,
                        },
                    ];
                })->all();
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
            $this->calculationError = 'Simulasi gagal dihitung. Periksa konfigurasi Product dan master kendaraan.';
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
        $this->traces = [];
        $this->outcome = null;
        $this->clearComputedOptions();
    }

    /* ------------------------------------------------------------- Jejak */

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
            ! $this->isModeB => 'Pencairan All In',
            default => 'Total DP',
        };
    }

    private function defaultProductId(): string
    {
        return (string) (Product::where('is_active', true)->orderBy('name')->value('id') ?? '');
    }

    private function defaultUsageId(): string
    {
        // From the filtered list, so the default cannot land on a usage the
        // selected financing type refuses.
        return (string) ($this->usages()->value('id') ?? '');
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
        $this->product_id = $this->defaultProductId();
        $this->financing_type = 'UCF';
        $this->simulation_profile = 'referral';
        $this->mode = 'A';
        $this->debtor_type = 'non_entrepreneur';
        $this->age_group_id = null;
        $this->usage_id = $this->defaultUsageId();
        $this->brand_id = null;
        $this->type_id = null;
        $this->model_id = null;
        $this->vehicle_year = null;
        $this->instalment_type = 'ADDB';
        $this->coverage_type = 'comprehensive_then_tlo';
        $this->stnk_ownership = 'own';
        $this->market_price = '';
        $this->up_rate = '0';
        $this->up_admin = '0';
        $this->up_provisi = '0';
        $this->up_acp = '';
        $this->desired_amount = '';
        $this->traced_tenor = 12;
    }

    private function clearComputedOptions(): void
    {
        unset(
            $this->products,
            $this->ageGroups,
            $this->usages,
            $this->brands,
            $this->vehicleTypes,
            $this->vehicleModels,
            $this->vehicleYears,
            $this->reachingCategories,
            $this->selectedProduct,
            $this->productRates,
            $this->isUcf,
            $this->isModeB,
            $this->isOfficer,
            $this->needsUnitPrice,
            $this->trace,
        );
    }

    public function render()
    {
        return view('livewire.admin.simulation.configuration-simulation');
    }
}
