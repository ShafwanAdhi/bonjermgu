<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Kebon Jeruk Multiguna' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-canvas">
    <header class="border-b border-hairline bg-canvas">
        <div class="band flex h-16 items-center gap-sm">
            <a href="{{ route('landing') }}" class="mr-auto flex items-center gap-2.5">
                <x-ui.wordmark />
            </a>

            <a href="{{ route('login') }}"
               class="rounded-lg border border-hairline bg-canvas px-5 py-[11px] text-button text-ink">
                Masuk
            </a>
            <a href="{{ route('register') }}"
               class="rounded-lg bg-primary px-5 py-3 text-button text-on-primary active:bg-primary-active">
                Registrasi
            </a>
        </div>
    </header>

    <main>
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
