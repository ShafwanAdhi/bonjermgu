<?php

namespace App\Livewire\Admin\Configuration;

use App\Livewire\Admin\AuditedAdminComponent;
use App\Models\FiduciaTier;
use App\Models\SimulationSetting;
use App\Models\SumInsuredSchedule;
use App\Services\ConfigurationIntegrityValidator;
use App\Support\RupiahInput;
use App\Support\SimulationSettingDefaults;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class Fees extends AuditedAdminComponent
{
    private const NET_DP_SETTING_LABELS = [
        'dtn_standard_net_dp_rate' => 'DTN · Standard',
        'dtn_high_risk_net_dp_rate' => 'DTN · Risiko Tinggi',
        'ucf_standard_net_dp_rate' => 'UCF · Standard',
        'ucf_non_japan_net_dp_rate' => 'UCF · Non Japan',
        'ucf_entrepreneur_net_dp_rate' => 'UCF · Wiraswasta',
    ];

    private const REFUND_SETTING_LABELS = [
        'ucf_insurance_refund_base_rate' => 'Refund Asuransi · Dasar',
        'ucf_insurance_refund_rate' => 'Refund Asuransi',
        'ucf_interest_refund_rate' => 'Refund Bunga',
        'ucf_provision_refund_rate' => 'Refund Provisi',
        'ucf_admin_refund_rate' => 'Refund Admin',
    ];

    /** @var array<int, array<string, mixed>> */
    public array $fiduciaTiers = [];

    /** @var array<int, array<string, mixed>> */
    public array $sumInsured = [];

    /** @var array<string, string> */
    public array $settings = [];

    public function mount(): void
    {
        $this->loadData();
    }

    public function addFiduciaTier(): void
    {
        $this->fiduciaTiers[] = ['id' => null, 'min_amount' => '', 'max_amount' => '', 'fee' => ''];
    }

    public function removeFiduciaTier(int $index): void
    {
        unset($this->fiduciaTiers[$index]);
        $this->fiduciaTiers = array_values($this->fiduciaTiers);
    }

    public function addSumInsuredYear(): void
    {
        $this->sumInsured[] = ['id' => null, 'year_index' => count($this->sumInsured) + 1, 'percentage' => ''];
    }

    public function removeSumInsuredYear(int $index): void
    {
        unset($this->sumInsured[$index]);
        $this->sumInsured = array_values($this->sumInsured);
    }

    public function save(ConfigurationIntegrityValidator $integrity): void
    {
        $this->fiduciaTiers = RupiahInput::normalizeRows($this->fiduciaTiers, [
            'min_amount',
            'max_amount',
            'fee',
        ]);

        $validated = $this->validate($this->rules(), [], $this->validationAttributes());

        DB::transaction(function () use ($validated, $integrity): void {
            $this->syncModels(
                FiduciaTier::class,
                $validated['fiduciaTiers'],
                fn (array $row) => [
                    'min_amount' => $row['min_amount'],
                    'max_amount' => $row['max_amount'] === null || $row['max_amount'] === ''
                        ? null
                        : $row['max_amount'],
                    'fee' => $row['fee'],
                ],
            );
            $this->syncModels(
                SumInsuredSchedule::class,
                $validated['sumInsured'],
                fn (array $row) => [
                    'year_index' => $row['year_index'],
                    'percentage' => $this->fraction($row['percentage']),
                ],
            );

            foreach ($validated['settings'] as $key => $value) {
                SimulationSetting::query()->updateOrCreate(
                    ['key' => $key],
                    ['value' => (string) $this->fraction($value)],
                );
            }

            $integrity->assertFees();
        });

        $this->loadData();
        $this->refreshAudit();
        session()->flash('admin_success', 'Fee, DP, dan refund berhasil disimpan.');
    }

    public function render(): View
    {
        return view('admin.configuration.fees', [
            'netDpSettingLabels' => self::NET_DP_SETTING_LABELS,
            'refundSettingLabels' => self::REFUND_SETTING_LABELS,
        ])->layout('components.layouts.app', ['title' => 'Biaya dan Down Payment — Kebon Jeruk Multiguna']);
    }

    private function loadData(): void
    {
        $this->fiduciaTiers = FiduciaTier::query()->orderBy('min_amount')->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'min_amount' => (string) $row->min_amount,
                'max_amount' => $row->max_amount === null ? '' : (string) $row->max_amount,
                'fee' => (string) $row->fee,
            ])->all();
        $this->sumInsured = SumInsuredSchedule::query()->orderBy('year_index')->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'year_index' => (string) $row->year_index,
                'percentage' => $this->percent($row->percentage),
            ])->all();

        $databaseSettings = SimulationSetting::query()
            ->whereIn('key', array_keys($this->settingLabels()))
            ->pluck('value', 'key');
        $defaults = SimulationSettingDefaults::values();
        $this->settings = [];

        foreach ($this->settingLabels() as $key => $label) {
            $value = $databaseSettings->get($key, $defaults[$key] ?? null);
            $this->settings[$key] = is_numeric($value) ? $this->percent((float) $value) : '';
        }

        $this->resetValidation();
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
        $rules = [
            'fiduciaTiers' => ['required', 'array', 'min:1'],
            'fiduciaTiers.*.id' => ['nullable', 'integer'],
            'fiduciaTiers.*.min_amount' => ['required', 'integer', 'min:0', 'distinct'],
            'fiduciaTiers.*.max_amount' => ['nullable', 'integer', 'min:0'],
            'fiduciaTiers.*.fee' => ['required', 'integer', 'min:0'],
            'sumInsured' => ['required', 'array', 'size:5'],
            'sumInsured.*.id' => ['nullable', 'integer'],
            'sumInsured.*.year_index' => ['required', 'integer', 'between:1,5', 'distinct'],
            'sumInsured.*.percentage' => ['required', 'numeric', 'between:0,100'],
        ];

        foreach (array_keys($this->settingLabels()) as $key) {
            $rules["settings.{$key}"] = ['required', 'numeric', 'between:0,100'];
        }

        return $rules;
    }

    /** @return array<string, string> */
    private function validationAttributes(): array
    {
        return [
            'fiduciaTiers.*.min_amount' => 'Batas Bawah Fiducia',
            'fiduciaTiers.*.max_amount' => 'Batas Atas Fiducia',
            'fiduciaTiers.*.fee' => 'Biaya Fiducia',
            'sumInsured.*.year_index' => 'Tahun Sum Insured',
            'sumInsured.*.percentage' => 'Persentase Sum Insured',
            'settings.*' => 'Nilai DP atau Refund',
        ];
    }

    protected function auditTables(): array
    {
        return ['fiducia_tiers', 'sum_insured_schedules', 'simulation_settings'];
    }

    protected function auditModule(): string
    {
        return 'configuration.fees';
    }

    private function fraction(string|int|float $percent): float
    {
        return (float) $percent / 100;
    }

    /** @return array<string, string> */
    private function settingLabels(): array
    {
        return self::NET_DP_SETTING_LABELS + self::REFUND_SETTING_LABELS;
    }

    private function percent(float $fraction): string
    {
        return rtrim(rtrim(number_format($fraction * 100, 6, '.', ''), '0'), '.');
    }
}
