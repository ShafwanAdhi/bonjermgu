<div class="bg-surface-soft px-lg py-xl md:px-xxl md:py-xxl">
    <div class="mx-auto max-w-[440px]">
        <x-ui.back-link :href="route('login')" class="mb-md" />

        <div class="rounded-lg border border-hairline bg-canvas p-xl sm:p-10">
            <h1 class="mb-2 text-title-lg text-ink">Atur Kata Sandi Baru</h1>
            <p class="mb-7 text-[14px] leading-[1.6] text-muted">
                Gunakan kata sandi baru yang mudah Anda ingat dan sulit ditebak orang lain.
            </p>

            <form wire:submit="submit" x-data="{ showPassword: false }" class="flex flex-col gap-md">
                <x-ui.field label="Kata Sandi Baru" required :error="$errors->first('password')">
                    <x-ui.input wire:model="password"
                                type="password"
                                x-bind:type="showPassword ? 'text' : 'password'"
                                autocomplete="new-password"
                                placeholder="Minimal 8 karakter"
                                :invalid="$errors->has('password')" />
                </x-ui.field>

                <x-ui.field label="Konfirmasi Kata Sandi Baru" required
                            :error="$errors->first('password_confirmation')">
                    <x-ui.input wire:model="password_confirmation"
                                type="password"
                                x-bind:type="showPassword ? 'text' : 'password'"
                                autocomplete="new-password"
                                placeholder="Ulangi kata sandi baru"
                                :invalid="$errors->has('password_confirmation')" />
                </x-ui.field>

                <label class="flex items-center gap-sm text-body-md text-body">
                    <input type="checkbox" x-model="showPassword" class="rounded-xs border-hairline">
                    Tampilkan kata sandi
                </label>

                <x-ui.button type="submit" class="w-full" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="submit">Simpan Kata Sandi</span>
                    <span wire:loading wire:target="submit">Menyimpan...</span>
                </x-ui.button>
            </form>
        </div>
    </div>
</div>
