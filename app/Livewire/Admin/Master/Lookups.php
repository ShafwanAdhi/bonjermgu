<?php

namespace App\Livewire\Admin\Master;

use App\Livewire\Admin\AuditedAdminComponent;
use App\Models\AcpUpping;
use App\Models\AgeGroup;
use App\Models\Domicile;
use App\Services\ConfigurationIntegrityValidator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

final class Lookups extends AuditedAdminComponent
{
    /** @var array<int, array{id: int|null, name: string}> */
    public array $domiciles = [];

    /** @var array<int, array{id: int|null, label: string, upping: string}> */
    public array $ageGroups = [];

    public function mount(): void
    {
        $this->loadData();
    }

    public function addDomicile(): void
    {
        $this->domiciles[] = ['id' => null, 'name' => ''];
    }

    public function removeDomicile(int $index): void
    {
        unset($this->domiciles[$index]);
        $this->domiciles = array_values($this->domiciles);
    }

    public function addAgeGroup(): void
    {
        $this->ageGroups[] = ['id' => null, 'label' => '', 'upping' => ''];
    }

    public function removeAgeGroup(int $index): void
    {
        unset($this->ageGroups[$index]);
        $this->ageGroups = array_values($this->ageGroups);
    }

    public function moveDomicile(int $index, int $direction): void
    {
        $this->move($this->domiciles, $index, $direction);
    }

    public function moveAgeGroup(int $index, int $direction): void
    {
        $this->move($this->ageGroups, $index, $direction);
    }

    public function save(ConfigurationIntegrityValidator $integrity): void
    {
        $validated = $this->validate([
            'domiciles' => ['required', 'array', 'min:1'],
            'domiciles.*.id' => ['nullable', 'integer'],
            'domiciles.*.name' => ['required', 'string', 'max:80', 'distinct:ignore_case'],
            'ageGroups' => ['required', 'array', 'min:1'],
            'ageGroups.*.id' => ['nullable', 'integer'],
            'ageGroups.*.label' => ['required', 'string', 'max:30', 'distinct:ignore_case'],
            'ageGroups.*.upping' => ['required', 'numeric', 'between:0,100'],
        ], [], [
            'domiciles.*.name' => 'Nama Domisili',
            'ageGroups.*.label' => 'Kelompok Usia',
            'ageGroups.*.upping' => 'Upping ACP',
        ]);

        DB::transaction(function () use ($validated, $integrity): void {
            $domicileIds = collect($validated['domiciles'])->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();
            Domicile::query()
                ->when($domicileIds !== [], fn ($query) => $query->whereNotIn('id', $domicileIds))
                ->get()
                ->each
                ->delete();

            foreach ($validated['domiciles'] as $index => $row) {
                $domicile = $row['id'] ? Domicile::query()->findOrFail($row['id']) : new Domicile;
                $domicile->fill(['name' => trim($row['name']), 'sort_order' => $index + 1])->save();
            }

            $ageGroupIds = collect($validated['ageGroups'])->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();
            AgeGroup::query()
                ->when($ageGroupIds !== [], fn ($query) => $query->whereNotIn('id', $ageGroupIds))
                ->get()
                ->each(function (AgeGroup $group): void {
                    $group->acpUpping?->delete();
                    $group->delete();
                });

            foreach ($validated['ageGroups'] as $index => $row) {
                $group = $row['id'] ? AgeGroup::query()->findOrFail($row['id']) : new AgeGroup;
                $group->fill(['label' => trim($row['label']), 'sort_order' => $index + 1])->save();
                AcpUpping::query()->updateOrCreate(
                    ['age_group_id' => $group->id],
                    ['upping' => (float) $row['upping'] / 100],
                );
            }

            $integrity->assertInsurance();
        });

        $this->loadData();
        $this->refreshAudit();
        session()->flash('admin_success', 'Domisili dan kelompok usia berhasil disimpan.');
    }

    public function render(): View
    {
        return view('admin.master.lookups')
            ->layout('components.layouts.app', ['title' => 'Domisili dan Kelompok Usia — Kebon Jeruk Multiguna']);
    }

    private function loadData(): void
    {
        $this->domiciles = Domicile::query()->orderBy('sort_order')->get()
            ->map(fn (Domicile $row) => ['id' => $row->id, 'name' => $row->name])
            ->all();
        $this->ageGroups = AgeGroup::query()->with('acpUpping')->orderBy('sort_order')->get()
            ->map(fn (AgeGroup $row) => [
                'id' => $row->id,
                'label' => $row->label,
                'upping' => $row->acpUpping
                    ? rtrim(rtrim(number_format($row->acpUpping->upping * 100, 6, '.', ''), '0'), '.')
                    : '',
            ])->all();
        $this->resetValidation();
    }

    /** @param array<int, mixed> $rows */
    private function move(array &$rows, int $index, int $direction): void
    {
        $target = $index + $direction;

        if (! isset($rows[$index], $rows[$target])) {
            return;
        }

        [$rows[$index], $rows[$target]] = [$rows[$target], $rows[$index]];
    }

    protected function auditTables(): array
    {
        return ['domiciles', 'age_groups', 'acp_uppings'];
    }
}
