<?php

namespace App\Livewire\Admin\Configuration;

use App\Livewire\Admin\AuditedAdminComponent;
use App\Models\InsuranceCascoRate;
use App\Models\SimulationSetting;
use App\Services\ConfigurationIntegrityValidator;
use App\Support\RupiahInput;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class Defaults extends AuditedAdminComponent
{
    private const DEFINITIONS = [
        'max_vehicle_age' => ['label' => 'Batas Usia Maksimal Unit', 'type' => 'positive_integer'],
        'engine_warranty_fee' => ['label' => 'Biaya Garansi Mesin', 'type' => 'money'],
        'active_insurance_zone' => ['label' => 'Wilayah Asuransi Aktif', 'type' => 'zone'],
        'active_rate_variant' => ['label' => 'Varian Rate Aktif', 'type' => 'variant'],
        'default_deposit_instalment_amount' => ['label' => 'Deposit / Titipan Angsuran', 'type' => 'money'],
        'default_bbnkb_amount' => ['label' => 'BBNKB', 'type' => 'money'],
        'default_pkb_amount' => ['label' => 'PKB', 'type' => 'money'],
        'default_invoice_amount' => ['label' => 'Faktur', 'type' => 'money'],
        'default_flood_enabled' => ['label' => 'Banjir Default', 'type' => 'boolean'],
        'default_earthquake_enabled' => ['label' => 'Gempa Default', 'type' => 'boolean'],
        'default_riot_enabled' => ['label' => 'Huru-hara Default', 'type' => 'boolean'],
        'default_terrorism_enabled' => ['label' => 'Teroris Default', 'type' => 'boolean'],
        'default_tjh_amount' => ['label' => 'TJH Default', 'type' => 'money'],
        'default_driver_coverage_amount' => ['label' => 'Pertanggungan Pengemudi Default', 'type' => 'money'],
        'default_passenger_coverage_amount' => ['label' => 'Pertanggungan Penumpang Default', 'type' => 'money'],
        'default_passenger_count' => ['label' => 'Jumlah Penumpang Default', 'type' => 'integer'],
        'default_engine_warranty_enabled' => ['label' => 'Garansi Mesin Default', 'type' => 'boolean'],
        'tjh_max_amount' => ['label' => 'Batas Maksimal TJH', 'type' => 'money'],
        'tjh_step_amount' => ['label' => 'Kelipatan TJH', 'type' => 'positive_integer'],
        'dtn_acp_enabled' => ['label' => 'ACP Dana Tunai', 'type' => 'boolean'],
        'ucf_acp_enabled' => ['label' => 'ACP Mobil Bekas', 'type' => 'boolean'],
    ];

    /** @var array<string, string> */
    public array $settings = [];

    public function mount(): void
    {
        $this->loadData();
    }

    public function save(ConfigurationIntegrityValidator $integrity): void
    {
        $this->normalizeMoneySettings();

        $validated = $this->validate($this->rules(), [], $this->validationAttributes());

        DB::transaction(function () use ($validated, $integrity): void {
            foreach ($validated['settings'] as $key => $value) {
                SimulationSetting::query()->updateOrCreate(
                    ['key' => $key],
                    ['value' => (string) $value],
                );
            }

            $integrity->assertDefaults();
            $integrity->assertInsurance();
        });

        $this->loadData();
        $this->refreshAudit();
        session()->flash('admin_success', 'Nilai default simulasi berhasil disimpan.');
    }

    private function normalizeMoneySettings(): void
    {
        foreach (self::DEFINITIONS as $key => $definition) {
            if ($definition['type'] === 'money') {
                $this->settings[$key] = RupiahInput::normalize($this->settings[$key] ?? '');
            }
        }
    }

    public function render(): View
    {
        return view('admin.configuration.defaults', [
            'definitions' => self::DEFINITIONS,
            'zones' => InsuranceCascoRate::query()->distinct()->orderBy('zone')->pluck('zone'),
        ])->layout('components.layouts.app', ['title' => 'Nilai Default Simulasi — Kebon Jeruk Multiguna']);
    }

    private function loadData(): void
    {
        $databaseSettings = SimulationSetting::query()
            ->whereIn('key', array_keys(self::DEFINITIONS))
            ->pluck('value', 'key');
        $this->settings = [];

        foreach (self::DEFINITIONS as $key => $definition) {
            $this->settings[$key] = (string) $databaseSettings->get($key, '');
        }

        $this->resetValidation();
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(): array
    {
        $rules = [];

        foreach (self::DEFINITIONS as $key => $definition) {
            $rules["settings.{$key}"] = match ($definition['type']) {
                'money', 'integer' => ['required', 'integer', 'min:0'],
                'positive_integer' => ['required', 'integer', 'min:1'],
                'boolean' => ['required', Rule::in(['true', 'false'])],
                'variant' => ['required', Rule::in(['Batas Atas', 'Batas Bawah'])],
                'zone' => ['required', 'string', 'max:30', Rule::exists('insurance_casco_rates', 'zone')],
            };
        }

        return $rules;
    }

    /** @return array<string, string> */
    private function validationAttributes(): array
    {
        $attributes = [];

        foreach (self::DEFINITIONS as $key => $definition) {
            $attributes["settings.{$key}"] = $definition['label'];
        }

        return $attributes;
    }

    protected function auditTables(): array
    {
        return ['simulation_settings'];
    }
}
