<?php

namespace App\Livewire\Admin;

use App\Models\AdminChangeLog;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Computed;
use Livewire\Component;

abstract class AuditedAdminComponent extends Component
{
    abstract protected function auditModule(): string;

    /** @return array<int, string> */
    abstract protected function auditTables(): array;

    public function boot(): void
    {
        app()->instance('admin.audit_module', $this->auditModule());
    }

    #[Computed]
    public function lastChange(): ?AdminChangeLog
    {
        $query = AdminChangeLog::query()
            ->whereIn('subject_table', $this->auditTables())
            ->latest('created_at');

        if (Schema::hasColumn('admin_change_logs', 'audit_module')) {
            $query->where('audit_module', $this->auditModule());
        }

        return $query->first();
    }

    protected function refreshAudit(): void
    {
        unset($this->lastChange);
    }
}
