@props(['route'])

@php
    $icon = match (true) {
        $route === 'dashboard' => 'dashboard',
        $route === 'simulation' => 'simulation',
        str_starts_with($route, 'applications.') => 'applications',
        $route === 'profile' => 'profile',
        $route === 'configuration.products' => 'product',
        $route === 'configuration.insurance' => 'shield',
        $route === 'configuration.fees' => 'receipt',
        $route === 'configuration.defaults' => 'defaults',
        $route === 'configuration.simulation' => 'calculator',
        $route === 'master.vehicles' => 'car',
        $route === 'master.referral' => 'referral-tree',
        $route === 'master.lookups' => 'lookups',
        $route === 'accounts.profile' => 'admin-profile',
        $route === 'accounts.referrals' => 'user-check',
        $route === 'accounts.officers' => 'id-card',
        $route === 'lending.ao' => 'lending-user',
        $route === 'lending.referrals' => 'lending-network',
        str_starts_with($route, 'configuration.') => 'configuration',
        str_starts_with($route, 'master.') => 'master',
        str_starts_with($route, 'accounts.') => 'accounts',
        $route === 'lending' || str_starts_with($route, 'lending.') => 'lending',
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

        @case('product')
            <path d="M5 6.5h14M5 12h14M5 17.5h14" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
            <path d="M8 8.5a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM16 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM10.5 19.5a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" stroke="currentColor" stroke-width="1.7" />
            @break

        @case('shield')
            <path d="M12 21s7-3.5 7-10.5V5.7L12 3 5 5.7v4.8C5 17.5 12 21 12 21Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
            <path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
            @break

        @case('receipt')
            <path d="M7 4h10a2 2 0 0 1 2 2v15l-3-1.5-3 1.5-3-1.5L7 21V4Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
            <path d="M9.5 8h5M9.5 12h5M9.5 16h3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
            @break

        @case('defaults')
            <path d="M6 5h12v14H6V5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
            <path d="M9 9h6M9 12h6M9 15h3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
            <path d="M17 3v4M7 3v4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
            @break

        @case('calculator')
            <path d="M7 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" />
            <path d="M8.5 7h7M8.5 11h.01M12 11h.01M15.5 11h.01M8.5 15h.01M12 15h.01M15.5 15h.01" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" />
            @break

        @case('car')
            <path d="M5 13l1.6-4.2A2 2 0 0 1 8.5 7.5h7a2 2 0 0 1 1.9 1.3L19 13" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
            <path d="M4.5 13h15v5h-2v-1.5h-11V18h-2v-5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
            <path d="M7.5 14.8h.01M16.5 14.8h.01" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" />
            @break

        @case('referral-tree')
            <path d="M12 7a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM6 22a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM18 22a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="1.7" />
            <path d="M12 7v3.5M12 10.5H6v5.5M12 10.5h6v5.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
            @break

        @case('lookups')
            <path d="M5 5.5h8M5 12h8M5 18.5h8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
            <path d="M17.5 8.5s2.5-2.1 2.5-4a2.5 2.5 0 0 0-5 0c0 1.9 2.5 4 2.5 4Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
            <path d="M17.5 4.5h.01" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" />
            @break

        @case('user-check')
            <path d="M9.5 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM3.5 20a6 6 0 0 1 10.5-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
            <path d="m15 18 2 2 4-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
            @break

        @case('admin-profile')
            <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="1.7" />
            <path d="M4.5 20a7.5 7.5 0 0 1 15 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
            <path d="M17.5 5.5h3M19 4v3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
            @break

        @case('id-card')
            <path d="M5 5h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" />
            <path d="M8.5 12a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM6.5 16a3 3 0 0 1 4 0M14 9h4M14 13h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
            @break

        @case('lending-user')
            <path d="M4.5 19.5h15M6 17V7M11 17v-5M16 17V9" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
            <path d="M18 5.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" stroke="currentColor" stroke-width="1.7" />
            @break

        @case('lending-network')
            <path d="M5 19V5M5 19h14" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
            <path d="M8 15l3-3 2.5 2 4-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M8 15h.01M11 12h.01M13.5 14h.01M17.5 9h.01" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" />
            @break

        @default
            <path d="M12 20a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z" stroke="currentColor" stroke-width="1.7" />
    @endswitch
</svg>
