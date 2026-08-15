@php
    $user = auth()->user();
    $navigation = $user->role->navigation();
    $primaryNavigation = $navigation;
    $moreNavigation = [];

    if ($user->role->value === 'admin') {
        $primaryNavigation = array_slice($navigation, 0, 3);
        $moreNavigation = array_slice($navigation, 3);
    }

    $moreActive = collect($moreNavigation)->contains(fn ($item) => request()->routeIs($item['match']));
    $initials = collect(explode(' ', $user->displayName()))
        ->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');
    $profileRoute = $user->role->value === 'admin' ? 'accounts.profile' : 'profile';
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Kebon Jeruk Multiguna' }}</title>

    <x-layouts.favicon />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-canvas pb-[72px] lg:pb-0" data-role="{{ $user->role->value }}">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[90] focus:rounded-sm focus:bg-primary focus:px-4 focus:py-3 focus:text-body-md focus:font-medium focus:text-on-primary">
        Lewati ke konten utama
    </a>

    @if ($user->role->value === 'referral')
        <div class="referral-scroll-progress" data-scroll-progress aria-hidden="true"></div>
    @endif

    <header class="border-b border-hairline bg-canvas">
        <div class="band flex h-16 items-center gap-md xl:gap-xl">
            <a href="{{ route('dashboard') }}" class="flex min-h-11 shrink-0 items-center" aria-label="Kebon Jeruk Multiguna, ke dashboard">
                <x-ui.wordmark />
            </a>

            {{-- Desktop menu. On small screens this moves to the bottom bar. --}}
            <nav class="hidden min-w-0 flex-1 self-stretch lg:flex lg:justify-center lg:gap-4 xl:gap-7" aria-label="Navigasi utama">
                @foreach ($primaryNavigation as $item)
                    <a href="{{ route($item['route']) }}"
                       data-motion-action
                       @if (request()->routeIs($item['match'])) aria-current="page" @endif
                       @class([
                           '-mb-px flex shrink-0 items-center gap-1.5 whitespace-nowrap border-b-2 px-2 text-body-md xl:px-2.5',
                           'border-primary text-ink' => request()->routeIs($item['match']),
                           'border-transparent text-muted' => ! request()->routeIs($item['match']),
                       ])>
                        <x-ui.nav-icon :route="$item['route']" class="hidden h-3.5 w-3.5 opacity-70 xl:block" />
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach

                @if ($moreNavigation !== [])
                    <div class="relative -mb-px flex shrink-0 items-center"
                         x-data="{ open: false }"
                         x-on:keydown.escape.window="open = false"
                         x-on:click.outside="open = false">
                        <button type="button"
                                data-motion-action
                                x-on:click="open = ! open"
                                x-bind:aria-expanded="open.toString()"
                                aria-controls="desktop-more-navigation"
                                aria-haspopup="menu"
                                aria-label="Buka menu navigasi lainnya"
                                @class([
                                    'flex h-full items-center gap-1.5 whitespace-nowrap border-b-2 px-2 text-body-md transition-colors xl:px-2.5',
                                    'border-primary text-ink' => $moreActive,
                                    'border-transparent text-muted hover:text-ink' => ! $moreActive,
                                ])>
                            <span>Lainnya</span>
                            <svg class="h-4 w-4 shrink-0 transition-transform"
                                 x-bind:class="{ 'rotate-180': open }"
                                 xmlns="http://www.w3.org/2000/svg"
                                 viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="2.25"
                                 stroke-linecap="round"
                                 stroke-linejoin="round"
                                 aria-hidden="true">
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                        </button>

                        <div x-cloak
                             x-show="open"
                             x-transition.origin.top
                             id="desktop-more-navigation"
                             class="absolute right-0 top-full z-50 mt-2 w-56 overflow-hidden rounded-md border border-hairline bg-canvas py-1 shadow-[0_18px_40px_rgba(24,29,38,0.12)]"
                             role="menu">
                            @foreach ($moreNavigation as $item)
                                <a href="{{ route($item['route']) }}"
                                   data-motion-action
                                   role="menuitem"
                                   @if (request()->routeIs($item['match'])) aria-current="page" @endif
                                   @class([
                                       'flex min-h-11 items-center gap-2 px-3 text-[13px] transition-colors',
                                       'bg-surface-soft font-medium text-ink' => request()->routeIs($item['match']),
                                       'text-muted hover:bg-surface-soft hover:text-ink' => ! request()->routeIs($item['match']),
                                   ])>
                                    <x-ui.nav-icon :route="$item['route']" class="h-3.5 w-3.5 opacity-70" />
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </nav>

            <div class="ml-auto flex shrink-0 items-center gap-sm">
                <a href="{{ route($profileRoute) }}"
                   wire:navigate
                   data-motion-action
                   aria-label="Buka profil {{ $user->displayName() }}"
                   class="flex aspect-square h-8 w-8 shrink-0 items-center justify-center rounded-full text-[12px] font-medium leading-none transition-transform active:scale-95 {{ $user->role->avatarClasses() }}">
                    {{ $initials }}
                </a>
                <span class="hidden flex-col gap-0.5 xl:flex">
                    <span class="text-[13px] font-medium leading-none text-ink">{{ $user->displayName() }}</span>
                    <span class="text-[11px] leading-none text-muted">{{ $user->role->label() }}</span>
                </span>
                <form method="POST" action="{{ route('logout') }}" class="ml-sm shrink-0 border-l border-hairline pl-sm md:pl-md">
                    @csrf
                    <button type="submit" aria-label="Keluar dari akun" class="inline-flex min-h-11 items-center rounded-sm px-3 text-[13px] leading-none text-muted transition-colors hover:text-ink">
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main id="main-content" tabindex="-1" class="bg-canvas" @if ($user->role->value === 'admin') data-admin-motion-page @endif>
        {{ $slot }}
    </main>

    {{-- Mobile tab bar, from Mobile.dc.html. Same destinations as the desktop
         menu — a Referral in the field gets the whole app, not a subset. --}}
    <nav class="fixed inset-x-0 bottom-0 z-40 flex border-t border-hairline bg-canvas lg:hidden" aria-label="Navigasi utama mobile">
        @foreach ($navigation as $item)
            <a href="{{ route($item['route']) }}"
               data-motion-action
               @if (request()->routeIs($item['match'])) aria-current="page" @endif
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
    @livewireScripts
</body>
</html>
