<div class="min-h-[calc(100vh-100px)] bg-surface-soft px-lg py-section md:px-xxl">
    <div class="mx-auto max-w-[400px] rounded-lg border border-hairline bg-canvas p-10">
        <h1 class="mb-2 text-title-lg text-ink">Masuk</h1>
        <p class="mb-7 text-[14px] leading-[1.6] text-muted">
            Gunakan nama user dan kata sandi Anda.
        </p>

        <form wire:submit="login" x-data="{ showPassword: false }" class="flex flex-col gap-md">
            <x-ui.field label="Nama User" :error="$errors->first('username')">
                <x-ui.input wire:model="username" type="text"
                            :invalid="$errors->has('username')" autocomplete="username" autofocus />
            </x-ui.field>

            <x-ui.field label="Kata Sandi" :error="$errors->first('password')">
                <x-ui.input wire:model="password" type="password"
                            x-bind:type="showPassword ? 'text' : 'password'"
                            :invalid="$errors->has('password')" autocomplete="current-password" />
            </x-ui.field>

            <label class="-mt-1 flex items-center gap-sm text-body-md text-body">
                <input type="checkbox" x-model="showPassword" class="rounded-xs border-hairline">
                Tampilkan kata sandi
            </label>

            <label class="flex items-center gap-sm text-body-md text-body">
                <input type="checkbox" wire:model="remember" class="rounded-xs border-hairline">
                Ingat saya
            </label>

            <x-ui.button type="submit" class="w-full" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="login">Masuk</span>
                <span wire:loading wire:target="login">Memeriksa…</span>
            </x-ui.button>
        </form>

        <p class="mt-5 text-center text-[13px] leading-[1.6] text-muted">
            Belum punya akun?
            <a href="{{ route('register') }}" class="text-link active:text-link-active">Registrasi</a>
        </p>
    </div>
</div>
