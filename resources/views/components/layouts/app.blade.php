@php
    $user = auth()->user();
    $navigation = $user->role->navigation();
    $initials = collect(explode(' ', $user->displayName()))
        ->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');
@endphp

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
</head>
<body class="bg-canvas pb-[72px] md:pb-0">
    <header class="border-b border-hairline bg-canvas">
        <div class="band flex h-16 items-center gap-xl">
            <a href="{{ route('dashboard') }}" class="shrink-0">
                <x-ui.wordmark />
            </a>

            {{-- Desktop menu. On small screens this moves to the bottom bar. --}}
            <nav class="hidden self-stretch md:flex md:gap-7">
                @foreach ($navigation as $item)
                    <a href="{{ route($item['route']) }}"
                       @class([
                           '-mb-px flex items-center gap-2 border-b-2 text-body-md',
                           'border-primary text-ink' => request()->routeIs($item['match']),
                           'border-transparent text-muted' => ! request()->routeIs($item['match']),
                       ])>
                        <x-ui.nav-icon :route="$item['route']" />
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            <div class="ml-auto flex items-center gap-sm">
                <span class="flex h-8 w-8 items-center justify-center rounded-pill text-[12px] font-medium {{ $user->role->avatarClasses() }}">
                    {{ $initials }}
                </span>
                <span class="hidden flex-col gap-0.5 sm:flex">
                    <span class="text-[13px] font-medium leading-none text-ink">{{ $user->displayName() }}</span>
                    <span class="text-[11px] leading-none text-muted">{{ $user->role->label() }}</span>
                </span>
                <form method="POST" action="{{ route('logout') }}" class="ml-sm border-l border-hairline pl-md">
                    @csrf
                    <button type="submit" class="text-[13px] leading-none text-muted">Keluar</button>
                </form>
            </div>
        </div>
    </header>

    <main class="bg-canvas">
        {{ $slot }}
    </main>

    {{-- Mobile tab bar, from Mobile.dc.html. Same destinations as the desktop
         menu — a Referral in the field gets the whole app, not a subset. --}}
    <nav class="fixed inset-x-0 bottom-0 z-40 flex border-t border-hairline bg-canvas md:hidden">
        @foreach ($navigation as $item)
            <a href="{{ route($item['route']) }}"
               @class([
                   'flex flex-1 flex-col items-center gap-1.5 py-3 text-center text-[12px] leading-[1.2]',
                   '-mt-px border-t-2 border-primary font-medium text-ink' => request()->routeIs($item['match']),
                   'text-muted' => ! request()->routeIs($item['match']),
               ])>
                <x-ui.nav-icon :route="$item['route']" class="h-[18px] w-[18px]" />
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>
</body>
</html>
