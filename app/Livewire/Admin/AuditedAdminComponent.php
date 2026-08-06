<?php

namespace App\Livewire\Admin;

use App\Models\AdminChangeLog;
use Livewire\Attributes\Computed;
use Livewire\Component;

abstract class AuditedAdminComponent extends Component
{
    /** @return array<int, string> */
    abstract protected function auditTables(): array;

    #[Computed]
    public function lastChange(): ?AdminChangeLog
    {
        return AdminChangeLog::query()
            ->whereIn('subject_table', $this->auditTables())
            ->latest('created_at')
            ->first();
    }

    protected function refreshAudit(): void
    {
        unset($this->lastChange);
    }
}
