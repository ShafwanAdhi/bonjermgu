<?php

namespace App\Livewire\Admin\Configuration;

use App\Livewire\Admin\AuditedAdminComponent;
use App\Models\AcpBaseRate;
use App\Models\AcpUpping;
use App\Models\AgeGroup;
use App\Models\InsuranceCascoRate;
use App\Models\InsuranceExtensionRate;
use App\Models\InsuranceLoadingRate;
use App\Models\TjhTier;
use App\Services\ConfigurationIntegrityValidator;
use App\Support\RupiahInput;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class Insurance extends AuditedAdminComponent
{
    private const CASCO_CELLS = [
        'passenger_comprehensive' => ['Passenger', 'Comprehensive'],
        'commercial_comprehensive' => ['Commercial', 'Comprehensive'],
        'passenger_tlo' => ['Passenger', 'TLO'],
        'commercial_tlo' => ['Commercial', 'TLO'],
    ];

    private const EXTENSION_LABELS = [
        'banjir' => 'Banjir',
        'gempa' => 'Gempa',
        'huru_hara' => 'Huru-hara',
        'teroris' => 'Teroris',
        'pengemudi' => 'Pengemudi',
        'penumpang' => 'Penumpang',
    ];

    public string $zone = '';

    public string $variant = 'Batas Bawah';

    public string $newZone = '';

    /** @var array<int, array<string, mixed>> */
    public array $cascoBands = [];

    /** @var array<int, array<string, mixed>> */
    public array $loadingRates = [];

    /** @var array<int, array<string, mixed>> */
    public array $extensionRates = [];

    /** @var array<int, array<string, mixed>> */
    public array $acpBaseRates = [];

    /** @var array<int, array<string, mixed>> */
    public array $acpUppings = [];

    /** @var array<int, array<string, mixed>> */
    public array $tjhTiers = [];

    public function mount(): void
    {
        $this->zone = (string) InsuranceCascoRate::query()->orderBy('zone')->value('zone');
        $this->loadCasco();
        $this->loadGlobalRates();
    }

    public function updatedZone(): void
    {
        $this->loadCasco();
    }

    public function updatedVariant(): void
    {
        $this->loadCasco();
    }

    public function selectNewZone(): void
    {
        $this->validate(['newZone' => ['required', 'string', 'max:30']], [], ['newZone' => 'Wilayah baru']);
        $this->zone = trim($this->newZone);
        $this->newZone = '';
        $this->cascoBands = [];
        $this->addCascoBand();
    }

    public function addCascoBand(): void
    {
        $this->cascoBands[] = [
            'ids' => [],
            'band_min' => '',
            'band_max' => '',
            'passenger_comprehensive' => '',
            'commercial_comprehensive' => '',
            'passenger_tlo' => '',
            'commercial_tlo' => '',
        ];
    }

    public function removeCascoBand(int $index): void
    {
        unset($this->cascoBands[$index]);
        $this->cascoBands = array_values($this->cascoBands);
    }

    public function addLoadingRate(): void
    {
        $this->loadingRates[] = ['id' => null, 'vehicle_age' => '', 'rate' => ''];
    }

    public function removeLoadingRate(int $index): void
    {
        unset($this->loadingRates[$index]);
        $this->loadingRates = array_values($this->loadingRates);
    }

    public function addTjhTier(): void
    {
        $this->tjhTiers[] = [
            'id' => null,
            'sequence' => count($this->tjhTiers) + 1,
            'limit_amount' => '',
            'rate' => '',
        ];
    }

    public function removeTjhTier(int $index): void
    {
        unset($this->tjhTiers[$index]);
        $this->tjhTiers = array_values($this->tjhTiers);
    }

    public function save(ConfigurationIntegrityValidator $integrity): void
    {
        $this->cascoBands = RupiahInput::normalizeRows($this->cascoBands, ['band_min', 'band_max']);
        $this->tjhTiers = RupiahInput::normalizeRows($this->tjhTiers, ['limit_amount']);

        $validated = $this->validate($this->rules(), [], $this->validationAttributes());

        DB::transaction(function () use ($validated, $integrity): void {
            $this->syncCasco($validated['cascoBands']);
            $this->syncModels(
                InsuranceLoadingRate::class,
                $validated['loadingRates'],
                fn (array $row) => [
                    'vehicle_age' => $row['vehicle_age'],
                    'rate' => $this->fraction($row['rate']),
                ],
            );
            $this->syncModels(
                InsuranceExtensionRate::class,
                $validated['extensionRates'],
                fn (array $row) => ['code' => $row['code'], 'rate' => $this->fraction($row['rate'])],
            );
            $this->syncModels(
                AcpBaseRate::class,
                $validated['acpBaseRates'],
                fn (array $row) => [
                    'tenor_years' => $row['tenor_years'],
                    'rate' => $this->fraction($row['rate']),
                ],
            );
            $this->syncModels(
                AcpUpping::class,
                $validated['acpUppings'],
                fn (array $row) => [
                    'age_group_id' => $row['age_group_id'],
                    'upping' => $this->fraction($row['upping']),
                ],
            );
            $this->syncModels(
                TjhTier::class,
                $validated['tjhTiers'],
                fn (array $row) => [
                    'sequence' => $row['sequence'],
                    'limit_amount' => $row['limit_amount'] === null || $row['limit_amount'] === ''
                        ? null
                        : $row['limit_amount'],
                    'rate' => $this->fraction($row['rate']),
                ],
            );

            $integrity->assertInsurance();
        });

        $this->loadCasco();
        $this->loadGlobalRates();
        $this->refreshAudit();
        session()->flash('admin_success', 'Konfigurasi Insurance berhasil disimpan sebagai satu konfigurasi lengkap.');
    }

    public function deleteCascoMatrix(ConfigurationIntegrityValidator $integrity): void
    {
        DB::transaction(function () use ($integrity): void {
            InsuranceCascoRate::query()
                ->where('zone', $this->zone)
                ->where('variant', $this->variant)
                ->get()
                ->each
                ->delete();

            $integrity->assertInsurance();
        });

        $this->loadCasco();
        $this->refreshAudit();
        session()->flash('admin_success', 'Matriks wilayah dan varian nonaktif berhasil dihapus.');
    }

    public function render(): View
    {
        return view('admin.configuration.insurance', [
            'zones' => InsuranceCascoRate::query()->distinct()->orderBy('zone')->pluck('zone'),
            'extensionLabels' => self::EXTENSION_LABELS,
        ])->layout('components.layouts.app', ['title' => 'Konfigurasi Asuransi — Kebon Jeruk Multiguna']);
    }

    private function loadCasco(): void
    {
        if ($this->zone === '') {
            $this->cascoBands = [];

            return;
        }

        $rates = InsuranceCascoRate::query()
            ->where('zone', $this->zone)
            ->where('variant', $this->variant)
            ->orderBy('band_min')
            ->get();

        $this->cascoBands = $rates
            ->groupBy('band_min')
            ->map(function ($bandRates): array {
                $first = $bandRates->first();
                $row = [
                    'ids' => [],
                    'band_min' => (string) $first->band_min,
                    'band_max' => $first->band_max === null ? '' : (string) $first->band_max,
                    'passenger_comprehensive' => '',
                    'commercial_comprehensive' => '',
                    'passenger_tlo' => '',
                    'commercial_tlo' => '',
                ];

                foreach (self::CASCO_CELLS as $key => [$usage, $coverage]) {
                    $rate = $bandRates->first(fn ($item) => $item->usage === $usage && $item->coverage === $coverage);

                    if ($rate) {
                        $row['ids'][$key] = $rate->id;
                        $row[$key] = $this->percent($rate->rate);
                    }
                }

                return $row;
            })->values()->all();

        $this->resetValidation();
    }

    private function loadGlobalRates(): void
    {
        $this->loadingRates = InsuranceLoadingRate::query()->orderBy('vehicle_age')->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'vehicle_age' => (string) $row->vehicle_age,
                'rate' => $this->percent($row->rate),
            ])->all();
        $this->extensionRates = InsuranceExtensionRate::query()->orderBy('code')->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'code' => $row->code,
                'rate' => $this->percent($row->rate),
            ])->all();
        $this->acpBaseRates = AcpBaseRate::query()->orderBy('tenor_years')->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'tenor_years' => (string) $row->tenor_years,
                'rate' => $this->percent($row->rate),
            ])->all();
        $this->acpUppings = AgeGroup::query()->with('acpUpping')->orderBy('sort_order')->get()
            ->map(fn (AgeGroup $group) => [
                'id' => $group->acpUpping?->id,
                'age_group_id' => $group->id,
                'label' => $group->label,
                'upping' => $group->acpUpping ? $this->percent($group->acpUpping->upping) : '',
            ])->all();
        $this->tjhTiers = TjhTier::query()->orderBy('sequence')->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'sequence' => (string) $row->sequence,
                'limit_amount' => $row->limit_amount === null ? '' : (string) $row->limit_amount,
                'rate' => $this->percent($row->rate),
            ])->all();
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function syncCasco(array $rows): void
    {
        $keptIds = collect($rows)
            ->flatMap(fn (array $row) => array_values($row['ids'] ?? []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        InsuranceCascoRate::query()
            ->where('zone', $this->zone)
            ->where('variant', $this->variant)
            ->when($keptIds !== [], fn ($query) => $query->whereNotIn('id', $keptIds))
            ->get()
            ->each
            ->delete();

        foreach ($rows as $row) {
            foreach (self::CASCO_CELLS as $key => [$usage, $coverage]) {
                $id = $row['ids'][$key] ?? null;
                $rate = $id ? InsuranceCascoRate::query()->findOrFail($id) : new InsuranceCascoRate;
                $rate->fill([
                    'zone' => $this->zone,
                    'variant' => $this->variant,
                    'usage' => $usage,
                    'coverage' => $coverage,
                    'band_min' => $row['band_min'],
                    'band_max' => $row['band_max'] === null || $row['band_max'] === '' ? null : $row['band_max'],
                    'rate' => $this->fraction($row[$key]),
                ])->save();
            }
        }
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<int, array<string, mixed>>  $rows
     * @param  callable(array<string, mixed>): array<string, mixed>  $attributes
     */
    private function syncModels(string $modelClass, array $rows, callable $attributes): void
    {
        $ids = collect($rows)->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();

        $modelClass::query()
            ->when($ids !== [], fn ($query) => $query->whereNotIn('id', $ids))
            ->get()
            ->each
            ->delete();

        foreach ($rows as $row) {
            /** @var Model $model */
            $model = ($row['id'] ?? null) ? $modelClass::query()->findOrFail($row['id']) : new $modelClass;
            $model->fill($attributes($row))->save();
        }
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(): array
    {
        return [
            'zone' => ['required', 'string', 'max:30'],
            'variant' => ['required', Rule::in(['Batas Atas', 'Batas Bawah'])],
            'cascoBands' => ['required', 'array', 'min:1'],
            'cascoBands.*.band_min' => ['required', 'integer', 'min:0', 'distinct'],
            'cascoBands.*.band_max' => ['nullable', 'integer', 'min:0'],
            'cascoBands.*.passenger_comprehensive' => ['required', 'numeric', 'between:0,100'],
            'cascoBands.*.commercial_comprehensive' => ['required', 'numeric', 'between:0,100'],
            'cascoBands.*.passenger_tlo' => ['required', 'numeric', 'between:0,100'],
            'cascoBands.*.commercial_tlo' => ['required', 'numeric', 'between:0,100'],
            'loadingRates' => ['required', 'array', 'min:15'],
            'loadingRates.*.id' => ['nullable', 'integer'],
            'loadingRates.*.vehicle_age' => ['required', 'integer', 'min:0', 'distinct'],
            'loadingRates.*.rate' => ['required', 'numeric', 'between:0,100'],
            'extensionRates' => ['required', 'array', 'size:6'],
            'extensionRates.*.id' => ['nullable', 'integer'],
            'extensionRates.*.code' => ['required', Rule::in(array_keys(self::EXTENSION_LABELS)), 'distinct'],
            'extensionRates.*.rate' => ['required', 'numeric', 'between:0,100'],
            'acpBaseRates' => ['required', 'array', 'size:5'],
            'acpBaseRates.*.id' => ['nullable', 'integer'],
            'acpBaseRates.*.tenor_years' => ['required', 'integer', 'between:1,5', 'distinct'],
            'acpBaseRates.*.rate' => ['required', 'numeric', 'between:0,100'],
            'acpUppings' => ['required', 'array'],
            'acpUppings.*.id' => ['nullable', 'integer'],
            'acpUppings.*.age_group_id' => ['required', 'integer', Rule::exists('age_groups', 'id'), 'distinct'],
            'acpUppings.*.upping' => ['required', 'numeric', 'between:0,100'],
            'tjhTiers' => ['required', 'array', 'min:1'],
            'tjhTiers.*.id' => ['nullable', 'integer'],
            'tjhTiers.*.sequence' => ['required', 'integer', 'min:1', 'distinct'],
            'tjhTiers.*.limit_amount' => ['nullable', 'integer', 'min:1'],
            'tjhTiers.*.rate' => ['required', 'numeric', 'between:0,100'],
        ];
    }

    /** @return array<string, string> */
    private function validationAttributes(): array
    {
        return [
            'zone' => 'Wilayah Asuransi',
            'variant' => 'Varian Rate',
            'cascoBands.*.band_min' => 'Batas Bawah Band',
            'cascoBands.*.band_max' => 'Batas Atas Band',
            'loadingRates.*.vehicle_age' => 'Usia Kendaraan',
            'loadingRates.*.rate' => 'Rate Loading',
            'extensionRates.*.rate' => 'Rate Perluasan',
            'acpBaseRates.*.rate' => 'Rate Dasar ACP',
            'acpUppings.*.upping' => 'Upping ACP',
            'tjhTiers.*.sequence' => 'Urutan TJH',
            'tjhTiers.*.limit_amount' => 'Batas Lapisan TJH',
            'tjhTiers.*.rate' => 'Rate TJH',
        ];
    }

    protected function auditTables(): array
    {
        return [
            'insurance_casco_rates',
            'insurance_loading_rates',
            'insurance_extension_rates',
            'acp_base_rates',
            'acp_uppings',
            'tjh_tiers',
        ];
    }

    protected function auditModule(): string
    {
        return 'configuration.insurance';
    }

    private function fraction(string|int|float $percent): float
    {
        return (float) $percent / 100;
    }

    private function percent(float $fraction): string
    {
        return rtrim(rtrim(number_format($fraction * 100, 6, '.', ''), '0'), '.');
    }
}
