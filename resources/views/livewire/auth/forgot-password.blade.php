<div class="bg-surface-soft px-lg py-xl md:px-xxl md:py-xxl">
    <div class="mx-auto max-w-[440px]">
        <x-ui.back-link :href="route('login')" class="mb-md" />

        <div class="rounded-lg border border-hairline bg-canvas p-xl sm:p-10">
            <h1 class="mb-2 text-title-lg text-ink">Lupa Kata Sandi</h1>
            <p class="mb-7 text-[14px] leading-[1.6] text-muted">
                Masukkan nama user. Kami akan mengirim link untuk membuat kata sandi baru ke email yang terpasang di profil akun.
            </p>

            @if ($sent)
                <x-ui.callout class="mb-md">
                    Jika nama user terdaftar dan memiliki email, link reset kata sandi sudah dikirim.
                </x-ui.callout>
            @endif

            <form wire:submit="submit" class="flex flex-col gap-md">
                <x-ui.field label="Nama User" required :error="$errors->first('username')">
                    <x-ui.input wire:model="username"
                                type="text"
                                autocomplete="username"
                                autofocus
                                placeholder="Masukkan nama user"
                                :invalid="$errors->has('username')" />
                </x-ui.field>

                <x-ui.button type="submit" class="w-full" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="submit">Kirim Link Reset</span>
                    <span wire:loading wire:target="submit">Mengirim...</span>
                </x-ui.button>
            </form>
        </div>
    </div>
</div>
