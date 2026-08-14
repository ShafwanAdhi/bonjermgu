<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Kebon Jeruk Multiguna' }}</title>

    <x-layouts.favicon />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-canvas">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[90] focus:rounded-sm focus:bg-primary focus:px-4 focus:py-3 focus:text-body-md focus:font-medium focus:text-on-primary">
        Lewati ke konten utama
    </a>

    <header class="border-b border-hairline bg-canvas">
        <div class="mx-auto flex h-14 max-w-container items-center gap-xs px-sm sm:h-16 sm:gap-sm sm:px-lg md:px-xxl">
            <a href="{{ route('landing') }}" class="mr-auto flex items-center gap-2.5" aria-label="Kebon Jeruk Multiguna, ke halaman utama">
                <x-ui.wordmark />
            </a>

            <a href="{{ route('login') }}"
               class="rounded-lg border border-hairline bg-canvas px-2.5 py-2 text-[12px] font-medium leading-[1.4] text-ink sm:px-5 sm:py-[11px] sm:text-button">
                Masuk
            </a>
            <a href="{{ route('register') }}"
               class="rounded-lg bg-primary px-2.5 py-2 text-[12px] font-medium leading-[1.4] text-on-primary active:bg-primary-active sm:px-5 sm:py-3 sm:text-button">
                Registrasi
            </a>
        </div>
    </header>

    <main id="main-content" tabindex="-1">
        {{ $slot }}
    </main>

    <footer class="border-t border-hairline">
        <div class="band flex flex-wrap items-center gap-lg py-xl">
            <div class="mr-auto flex items-center gap-2.5">
                <x-ui.wordmark size="sm" />
            </div>
            <a href="{{ route('register') }}" class="text-body-md text-muted">Registrasi</a>
            <a href="{{ route('login') }}" class="text-body-md text-muted">Masuk</a>
        </div>
    </footer>
    @livewireScripts
</body>
</html>
