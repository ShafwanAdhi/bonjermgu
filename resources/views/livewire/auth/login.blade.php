<div class="bg-surface-soft px-lg py-xl md:px-xxl md:py-xxl">
    <div class="mx-auto max-w-[400px]">
        <x-ui.back-link :href="route('landing')" class="mb-md" />

        <div class="rounded-lg border border-hairline bg-canvas p-xl sm:p-10">
            <h1 class="mb-2 text-title-lg text-ink">Masuk</h1>
            <p class="mb-7 text-[14px] leading-[1.6] text-muted">
                Gunakan nama user dan kata sandi Anda.
            </p>

            @if (session('reset_success'))
                <x-ui.callout class="mb-md">{{ session('reset_success') }}</x-ui.callout>
            @endif

        <form wire:submit="login" x-data="{ showPassword: false }" class="flex flex-col gap-md">
            <x-ui.field label="Nama User" :error="$errors->first('username')">
                <x-ui.input wire:model="username" type="text"
                            :invalid="$errors->has('username')" autocomplete="username" autofocus
                            placeholder="Masukkan nama user" />
            </x-ui.field>

            <x-ui.field label="Kata Sandi" :error="$errors->first('password')">
                <div class="relative">
                    <x-ui.input wire:model="password" type="password"
                                x-bind:type="showPassword ? 'text' : 'password'"
                                :invalid="$errors->has('password')" autocomplete="current-password"
                                placeholder="Masukkan kata sandi" class="pr-12" />

                    <button type="button"
                            class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-muted transition-colors hover:text-ink"
                            x-bind:aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                            x-on:click="showPassword = ! showPassword">
                        <svg x-show="! showPassword" x-cloak xmlns="http://www.w3.org/2000/svg"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             class="h-5 w-5" aria-hidden="true">
                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                        <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             class="h-5 w-5" aria-hidden="true">
                            <path d="m2 2 20 20" />
                            <path d="M6.71 6.71C3.92 8.57 2 12 2 12s3.5 7 10 7c1.78 0 3.34-.52 4.65-1.27" />
                            <path d="M10.73 5.08C11.14 5.03 11.56 5 12 5c6.5 0 10 7 10 7a18.5 18.5 0 0 1-2.1 3.13" />
                            <path d="M14.12 14.12A3 3 0 0 1 9.88 9.88" />
                        </svg>
                    </button>
                </div>
            </x-ui.field>

            <div class="flex items-center justify-between gap-md">
                <label class="flex items-center gap-sm text-body-md text-body">
                    <input type="checkbox" wire:model="remember" class="rounded-xs border-hairline">
                    Ingat saya
                </label>

                <a href="{{ route('password.request') }}" class="text-body-md text-link active:text-link-active">
                    Lupa kata sandi?
                </a>
            </div>

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
</div>
