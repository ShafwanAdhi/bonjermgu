<?php

namespace App\Livewire\Admin\Configuration;

use App\Models\AdminChangeLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Riwayat Perubahan Konfigurasi — the reader for `admin_change_logs`.
 *
 * AD-17 already writes actor, timestamp, and before/after snapshots on every
 * config and master data change. Until this page, `AuditedAdminComponent`
 * only ever read the single latest row per module for the "terakhir diubah"
 * caption — the full history and the before/after values themselves had no
 * screen. This is that screen: chronological, filterable by module, read
 * only. No rollback.
 */
#[Layout('components.layouts.app')]
class AuditLog extends Component
{
    use WithPagination;

    /** Module labels, in the order they appear on the module grids. */
    private const MODULES = [
        'configuration.products' => 'Product dan Upping',
        'configuration.insurance' => 'Konfigurasi Asuransi',
        'configuration.fees' => 'Biaya dan Down Payment',
        'configuration.defaults' => 'Nilai Default Simulasi',
        'master.vehicles' => 'Master Kendaraan',
        'master.referral' => 'Master Referral',
        'master.lookups' => 'Domisili dan Kelompok Usia',
    ];

    private const ACTIONS = [
        'created' => 'Ditambahkan',
        'updated' => 'Diubah',
        'deleted' => 'Dihapus',
    ];

    #[Url(as: 'module', except: '')]
    public string $module = '';

    #[Url(as: 'aksi', except: '')]
    public string $action = '';

    public function updatedModule(): void
    {
        $this->resetPage();
    }

    public function updatedAction(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function entries(): LengthAwarePaginator
    {
        return AdminChangeLog::query()
            ->with('actor:id,username')
            ->when($this->module !== '', fn ($q) => $q->where('audit_module', $this->module))
            ->when($this->action !== '', fn ($q) => $q->where('action', $this->action))
            ->latest('created_at')
            ->paginate(20);
    }

    /** @return array<string, string> */
    #[Computed]
    public function modules(): array
    {
        return self::MODULES;
    }

    /** @return array<string, string> */
    #[Computed]
    public function actions(): array
    {
        return self::ACTIONS;
    }

    public function clearFilters(): void
    {
        $this->reset('module', 'action');
        $this->resetPage();
    }

    public static function moduleLabel(?string $module): string
    {
        return self::MODULES[$module] ?? ($module ?? '—');
    }

    public static function actionLabel(string $action): string
    {
        return self::ACTIONS[$action] ?? $action;
    }

    public function render()
    {
        return view('livewire.admin.configuration.audit-log');
    }
}
