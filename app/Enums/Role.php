<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Referral = 'referral';
    case AccountOfficer = 'ao';

    /** Indonesian label for the interface. */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Referral => 'Referral',
            self::AccountOfficer => 'Account Officer',
        };
    }

    /**
     * Landing route after login. Every role shares the /dashboard route and
     * sees different content, per docs/pages.md section 2.
     */
    public function homeRoute(): string
    {
        return 'dashboard';
    }

    /**
     * Top navigation for this role, per docs/pages.md section 3. A menu a role
     * cannot reach is absent rather than disabled.
     *
     * Hiding a menu is presentation, not authorization. The gate that matters
     * is on the route and, once the data layer exists, on the query itself.
     *
     * @return array<int, array{label: string, route: string, match: string}>
     */
    public function navigation(): array
    {
        return match ($this) {
            self::Referral => [
                ['label' => 'Dashboard', 'route' => 'dashboard', 'match' => 'dashboard'],
                ['label' => 'Simulasi Kredit', 'route' => 'simulation', 'match' => 'simulation*'],
                ['label' => 'Aplikasi', 'route' => 'applications.index', 'match' => 'applications.*'],
                ['label' => 'Profil', 'route' => 'profile', 'match' => 'profile'],
            ],
            self::AccountOfficer => [
                ['label' => 'Dashboard', 'route' => 'dashboard', 'match' => 'dashboard'],
                ['label' => 'Aplikasi', 'route' => 'applications.index', 'match' => 'applications.*'],
                ['label' => 'Profil', 'route' => 'profile', 'match' => 'profile'],
            ],
            self::Admin => [
                ['label' => 'Dashboard', 'route' => 'dashboard', 'match' => 'dashboard'],
                ['label' => 'Konfigurasi', 'route' => 'configuration.products', 'match' => 'configuration.*'],
                ['label' => 'Master Data', 'route' => 'master.vehicles', 'match' => 'master.*'],
                ['label' => 'Akun', 'route' => 'accounts.referrals', 'match' => 'accounts.*'],
                ['label' => 'Lending', 'route' => 'lending', 'match' => 'lending'],
            ],
        };
    }

    /** Avatar tint, so the three roles are distinguishable at a glance. */
    public function avatarClasses(): string
    {
        return match ($this) {
            self::Referral => 'bg-signature-mint text-ink',
            self::AccountOfficer => 'bg-signature-peach text-ink',
            self::Admin => 'bg-primary text-on-primary',
        };
    }
}
