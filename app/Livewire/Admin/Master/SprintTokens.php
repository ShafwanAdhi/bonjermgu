<?php

namespace App\Livewire\Admin\Master;

use App\Livewire\Admin\AuditedAdminComponent;
use App\Models\SprintToken;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Master vocabulary behind the SPRINT Product ID and Product Offering.
 *
 * Editing here is how head office renaming a product or opening a channel gets
 * into the system, so the save guards the two ways a well-meaning edit breaks
 * View Sprint silently: a group that spells a segment losing its token, and a
 * sub-category pointing at a channel that no longer exists. Either one leaves
 * the AO with a permanently blank code and no reason on screen.
 */
final class SprintTokens extends AuditedAdminComponent
{
    /** @var array<string, array<int, array{id: int|null, source: string, product_token: string, offering_token: string}>> */
    public array $groups = [];

    public function mount(): void
    {
        $this->loadData();
    }

    public function addToken(string $group): void
    {
        $this->groups[$group][] = ['id' => null, 'source' => '', 'product_token' => '', 'offering_token' => ''];
    }

    public function removeToken(string $group, int $index): void
    {
        unset($this->groups[$group][$index]);
        $this->groups[$group] = array_values($this->groups[$group]);
    }

    public function moveToken(string $group, int $index, int $direction): void
    {
        $target = $index + $direction;

        if (! isset($this->groups[$group][$index], $this->groups[$group][$target])) {
            return;
        }

        [$this->groups[$group][$index], $this->groups[$group][$target]]
            = [$this->groups[$group][$target], $this->groups[$group][$index]];
    }

    public function save(): void
    {
        $rules = ['groups' => ['required', 'array']];
        $attributes = [];

        foreach (array_keys(SprintToken::GROUPS) as $group) {
            $rules["groups.{$group}"] = ['required', 'array', 'min:1'];
            $rules["groups.{$group}.*.id"] = ['nullable', 'integer'];
            $rules["groups.{$group}.*.source"] = ['required', 'string', 'max:64', 'distinct:ignore_case'];
            $rules["groups.{$group}.*.product_token"] = [
                in_array($group, [...SprintToken::PRODUCT_ID_PARTS, 'instalment'], true) ? 'required' : 'nullable',
                'string', 'max:64',
            ];
            $rules["groups.{$group}.*.offering_token"] = ['required', 'string', 'max:64'];

            $label = SprintToken::GROUPS[$group];
            $attributes["groups.{$group}.*.source"] = "Pilihan {$label}";
            $attributes["groups.{$group}.*.product_token"] = "Token Product ID {$label}";
            $attributes["groups.{$group}.*.offering_token"] = "Token Offering {$label}";
        }

        $validated = $this->validate($rules, [], $attributes);

        $this->assertChannelSourcesResolve($validated['groups']);

        DB::transaction(function () use ($validated): void {
            $keptIds = collect($validated['groups'])->flatten(1)->pluck('id')->filter()
                ->map(fn ($id) => (int) $id)->all();

            SprintToken::query()
                ->when($keptIds !== [], fn ($query) => $query->whereNotIn('id', $keptIds))
                ->get()
                ->each
                ->delete();

            foreach ($validated['groups'] as $group => $rows) {
                foreach ($rows as $position => $row) {
                    $token = $row['id'] ? SprintToken::query()->findOrFail($row['id']) : new SprintToken;
                    $token->fill([
                        'group_key' => $group,
                        'source' => trim($row['source']),
                        'product_token' => trim((string) $row['product_token']) ?: null,
                        'offering_token' => trim($row['offering_token']),
                        'position' => $position,
                    ])->save();
                }
            }
        });

        $this->loadData();
        $this->refreshAudit();
        session()->flash('admin_success', 'Token SPRINT berhasil disimpan.');
    }

    /**
     * Every sub-category must point at a channel that exists, otherwise View
     * Sprint quietly stops pre-filling the Kanal dropdown for those AOs.
     *
     * @param  array<string, array<int, array<string, mixed>>>  $groups
     */
    private function assertChannelSourcesResolve(array $groups): void
    {
        $channels = collect($groups['channel'] ?? [])->pluck('source')->map(trim(...))->all();

        foreach ($groups['channel_source'] ?? [] as $index => $row) {
            if (in_array(trim((string) $row['offering_token']), $channels, true)) {
                continue;
            }

            throw ValidationException::withMessages([
                "groups.channel_source.{$index}.offering_token" => sprintf(
                    'Kanal "%s" tidak ada pada daftar Kanal.',
                    trim((string) $row['offering_token']),
                ),
            ]);
        }
    }

    public function render(): View
    {
        return view('admin.master.sprint-tokens')
            ->layout('components.layouts.app', ['title' => 'Token SPRINT — Kebon Jeruk Multiguna']);
    }

    private function loadData(): void
    {
        $stored = SprintToken::grouped();

        $this->groups = collect(SprintToken::GROUPS)
            ->map(fn (string $label, string $group) => collect($stored[$group] ?? [])
                ->map(fn (SprintToken $row) => [
                    'id' => $row->id,
                    'source' => $row->source,
                    'product_token' => (string) $row->product_token,
                    'offering_token' => (string) $row->offering_token,
                ])->all())
            ->all();

        $this->resetValidation();
    }

    protected function auditTables(): array
    {
        return ['sprint_tokens'];
    }

    protected function auditModule(): string
    {
        return 'master.sprint_tokens';
    }
}
