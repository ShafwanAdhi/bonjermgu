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
                <div class="rounded-lg border border-hairline bg-canvas" wire:key="audit-{{ $entry->id }}" x-data="{ open: false }">
                    <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open"
                            class="flex w-full flex-wrap items-center justify-between gap-sm px-md py-3.5 text-left">
                        <div class="flex flex-wrap items-center gap-sm">
                            <x-ui.chip :tone="match ($entry->action) {
                                'created' => 'success',
                                'deleted' => 'danger',
                                default => 'neutral',
                            }">{{ AuditLog::actionLabel($entry->action) }}</x-ui.chip>
                            <span class="text-body-md font-medium text-ink">{{ AuditLog::moduleLabel($entry->audit_module) }}</span>
                            <span class="text-helper text-muted">{{ $entry->subject_table }} #{{ $entry->subject_id }}</span>
                        </div>
                        <div class="flex items-center gap-sm text-helper text-muted">
                            <span>{{ $entry->actor?->username ?? $entry->actor_name }}</span>
                            <span>{{ $entry->created_at->translatedFormat('d F Y H.i') }}</span>
                            <span x-text="open ? '−' : '+'" aria-hidden="true"></span>
                        </div>
                    </button>

                    <div x-show="open" x-cloak
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         class="border-t border-divider px-md py-3.5">
                        @if ($keys->isEmpty())
                            <p class="text-helper text-muted">Tidak ada rincian kolom untuk perubahan ini.</p>
                        @else
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-[minmax(0,180px)_1fr_1fr]">
                                <span class="hidden text-helper font-medium text-muted sm:block">Kolom</span>
                                <span class="hidden text-helper font-medium text-muted sm:block">Sebelum</span>
                                <span class="hidden text-helper font-medium text-muted sm:block">Sesudah</span>
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
                                    <div class="contents">
                                        <span class="font-mono text-[13px] text-ink">{{ $key }}</span>
                                        <span class="text-[13px] {{ $entry->action !== 'created' && $before !== $after ? 'text-signature-coral' : 'text-body' }}">
                                            {{ $entry->action === 'created' ? '—' : $render($before) }}
                                        </span>
                                        <span class="text-[13px] {{ $entry->action !== 'created' && $before !== $after ? 'font-medium text-ink' : 'text-body' }}">
                                            {{ $entry->action === 'deleted' ? '—' : $render($after) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
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
