@props(['route'])

@php
    $icon = match (true) {
        $route === 'dashboard' => 'dashboard',
        $route === 'simulation' => 'simulation',
        str_starts_with($route, 'applications.') => 'applications',
        $route === 'profile' => 'profile',
        str_starts_with($route, 'configuration.') => 'configuration',
        str_starts_with($route, 'master.') => 'master',
        str_starts_with($route, 'accounts.') => 'accounts',
        $route === 'lending' => 'lending',
        default => 'circle',
    };
@endphp

<svg {{ $attributes->class('h-4 w-4 shrink-0') }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
    @switch($icon)
        @case('dashboard')
            <path d="M4 13.5h6.5V4H4v9.5Zm9.5 6.5H20v-9.5h-6.5V20ZM4 20h6.5v-3.5H4V20Zm9.5-12.5H20V4h-6.5v3.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
            @break

        @case('simulation')
            <path d="M5 6.5h14M7 11h10M9 15.5h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
            <path d="M6 20h12a2 2 0 0 0 2-2V5.5A2.5 2.5 0 0 0 17.5 3h-11A2.5 2.5 0 0 0 4 5.5V18a2 2 0 0 0 2 2Z" stroke="currentColor" stroke-width="1.7" />
            @break

        @case('applications')
            <path d="M7 4h10a2 2 0 0 1 2 2v14H5V6a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
            <path d="M8 8h8M8 12h8M8 16h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
            @break

        @case('profile')
            <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="1.7" />
            <path d="M4.5 20a7.5 7.5 0 0 1 15 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
            @break

        @case('configuration')
            <path d="M5 7h14M5 17h14" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
            <path d="M9 9.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5ZM15 19.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" stroke="currentColor" stroke-width="1.7" />
            @break

        @case('master')
            <path d="M4 6.5 12 3l8 3.5-8 3.5-8-3.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
            <path d="m4 12 8 3.5 8-3.5M4 17.5 12 21l8-3.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
            @break

        @case('accounts')
            <path d="M9 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM15.5 10.5a3 3 0 1 0 0-6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
            <path d="M3.5 20a5.5 5.5 0 0 1 11 0M14.5 17.5a4.5 4.5 0 0 1 6 2.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
            @break

        @case('lending')
            <path d="M5 19V5M5 19h14" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
            <path d="M8 15.5 11.5 12l3 2.5L19 9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
            @break

        @default
            <path d="M12 20a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z" stroke="currentColor" stroke-width="1.7" />
    @endswitch
</svg>
