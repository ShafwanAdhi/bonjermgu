@use('App\Livewire\Admin\Configuration\AuditLog')

<div class="band py-xl md:py-xxl">
    <x-ui.back-link :href="route('configuration.index')" class="mb-md" />

    <div class="mb-xl">
        <p class="mb-1 text-eyebrow uppercase text-muted">Konfigurasi</p>
        <h1 class="m-0 font-display text-display-md text-ink">Riwayat Perubahan</h1>
    </div>

    <p class="mb-lg text-body-md text-body">
        Jejak setiap perubahan konfigurasi simulasi dan master data, dari yang terbaru.
        Bacaan saja — halaman ini tidak mengembalikan nilai lama.
    </p>

    <div class="mb-lg grid gap-sm md:grid-cols-[220px_200px_auto] md:items-end">
        <x-ui.field label="Module">
            <x-ui.select wire:model.live="module">
                <option value="">Semua</option>
                @foreach ($this->modules as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-ui.select>
        </x-ui.field>

        <x-ui.field label="Aksi">
            <x-ui.select wire:model.live="action">
                <option value="">Semua</option>
                @foreach ($this->actions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-ui.select>
        </x-ui.field>

        @if ($module !== '' || $action !== '')
            <button type="button" wire:click="clearFilters"
                    class="inline-flex min-h-11 items-center rounded-sm px-3 text-[13px] font-medium text-link md:justify-self-start">
                Bersihkan
            </button>
        @endif
    </div>

    <div wire:loading.class="opacity-60" wire:target="module,action,gotoPage,nextPage,previousPage">
        <div class="flex flex-col gap-sm" data-motion-stagger>
            @forelse ($this->entries as $entry)
                @php
                    $keys = collect(array_keys($entry->before_values ?? []))
                        ->merge(array_keys($entry->after_values ?? []))
                        ->unique()
                        ->sort()
                        ->values();
                @endphp
                <div class="overflow-hidden rounded-lg border border-hairline bg-canvas" wire:key="audit-{{ $entry->id }}" x-data="{ open: false }">
                    <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open"
                            class="flex w-full items-start gap-md px-md py-4 text-left transition-colors hover:bg-surface-soft">
                        <div class="min-w-0 flex-1">
                            <div class="flex min-w-0 flex-wrap items-center gap-xs sm:gap-sm">
                                <x-ui.chip :tone="match ($entry->action) {
                                    'created' => 'success',
                                    'deleted' => 'danger',
                                    default => 'neutral',
                                }">{{ AuditLog::actionLabel($entry->action) }}</x-ui.chip>
                                <span class="min-w-0 text-body-md font-medium text-ink">{{ AuditLog::moduleLabel($entry->audit_module) }}</span>
                            </div>

                            <div class="mt-2 flex min-w-0 flex-col gap-1 text-helper text-muted sm:flex-row sm:flex-wrap sm:items-center sm:gap-sm">
                                <span class="min-w-0 break-words font-mono">{{ $entry->subject_table }} #{{ $entry->subject_id }}</span>
                                <span class="hidden text-border-strong sm:inline" aria-hidden="true">&middot;</span>
                                <span>{{ $entry->actor?->username ?? $entry->actor_name }}</span>
                                <span class="hidden text-border-strong sm:inline" aria-hidden="true">&middot;</span>
                                <time datetime="{{ $entry->created_at->toIso8601String() }}">{{ $entry->created_at->translatedFormat('d F Y H.i') }}</time>
                            </div>
                        </div>
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-hairline bg-canvas text-[18px] leading-none text-muted transition-transform duration-200 motion-reduce:transition-none"
                              x-bind:class="open ? 'rotate-180' : 'rotate-0'"
                              x-text="open ? '-' : '+'"
                              aria-hidden="true"></span>
                    </button>

                    <div class="grid border-t transition-[grid-template-rows,opacity,border-color] duration-300 ease-out motion-reduce:transition-none"
                         x-bind:class="open ? 'grid-rows-[1fr] border-divider opacity-100' : 'grid-rows-[0fr] border-transparent opacity-0'">
                        <div class="min-h-0 overflow-hidden">
                            <div class="px-md py-3.5 transition-transform duration-300 ease-out motion-reduce:transition-none"
                                 x-bind:class="open ? 'translate-y-0' : '-translate-y-2'">
                                @if ($keys->isEmpty())
                                    <p class="text-helper text-muted">Tidak ada rincian kolom untuk perubahan ini.</p>
                                @else
                                    <div class="space-y-2">
                                        <div class="hidden grid-cols-[minmax(0,180px)_minmax(0,1fr)_minmax(0,1fr)] gap-md px-sm text-helper font-medium text-muted sm:grid">
                                            <span>Kolom</span>
                                            <span>Sebelum</span>
                                            <span>Sesudah</span>
                                        </div>
                                        @foreach ($keys as $key)
                                            @php
                                                $before = data_get($entry->before_values, $key);
                                                $after = data_get($entry->after_values, $key);
                                                $render = fn ($value) => match (true) {
                                                    $value === null => '—',
                                                    is_bool($value) => $value ? 'true' : 'false',
                                                    is_array($value) => json_encode($value),
                                                    default => (string) $value,
                                                };
                                            @endphp
                                            <div class="rounded-md border border-divider bg-canvas p-sm sm:grid sm:grid-cols-[minmax(0,180px)_minmax(0,1fr)_minmax(0,1fr)] sm:gap-md sm:border-0 sm:bg-transparent sm:px-sm sm:py-2">
                                                <div class="mb-2 min-w-0 sm:mb-0">
                                                    <span class="block text-[11px] font-medium uppercase leading-none text-muted sm:hidden">Kolom</span>
                                                    <span class="mt-1 block break-words font-mono text-[13px] text-ink sm:mt-0">{{ $key }}</span>
                                                </div>
                                                <div class="grid gap-xs sm:contents">
                                                    <div class="min-w-0 rounded-sm bg-surface-soft px-3 py-2 sm:bg-transparent sm:p-0">
                                                        <span class="block text-[11px] font-medium uppercase leading-none text-muted sm:hidden">Sebelum</span>
                                                        <span class="mt-1 block break-words text-[13px] leading-[1.5] {{ $entry->action !== 'created' && $before !== $after ? 'text-signature-coral' : 'text-body' }}">
                                                            {{ $entry->action === 'created' ? '—' : $render($before) }}
                                                        </span>
                                                    </div>
                                                    <div class="min-w-0 rounded-sm bg-surface-soft px-3 py-2 sm:bg-transparent sm:p-0">
                                                        <span class="block text-[11px] font-medium uppercase leading-none text-muted sm:hidden">Sesudah</span>
                                                        <span class="mt-1 block break-words text-[13px] leading-[1.5] {{ $entry->action !== 'created' && $before !== $after ? 'font-medium text-ink' : 'text-body' }}">
                                                            {{ $entry->action === 'deleted' ? '—' : $render($after) }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-lg border border-hairline bg-canvas px-5 py-xl text-center">
                    <p class="text-body-md text-ink">
                        @if ($module !== '' || $action !== '')
                            Tidak ada perubahan yang cocok dengan penyaring ini.
                        @else
                            Belum ada perubahan konfigurasi yang tercatat.
                        @endif
                    </p>
                </div>
            @endforelse
        </div>

        @if ($this->entries->hasPages())
            <div class="mt-lg">{{ $this->entries->links() }}</div>
        @endif
    </div>
</div>
