<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\AccountOfficer;
use App\Models\Admin;
use App\Models\Referral;
use App\Models\ReferralCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Development accounts: one Admin, two AO, three Referral.
 *
 * These credentials are public development credentials, so the seeder refuses
 * to run in production.
 *
 * Real accounts are created through their proper paths: Referral registers
 * itself (docs/actors.md section 8), AO is created by an Admin. How the first
 * Admin comes to exist is still open (docs/actors.md section 11, item 2).
 *
 * Idempotent: safe to run repeatedly.
 */
class UserSeeder extends Seeder
{
    private const DEV_ADMIN_PASSWORD = 'admin1234';

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('UserSeeder dilewati: akun contoh tidak dibuat di production.');

            return;
        }

        if (ReferralCategory::count() === 0) {
            throw new RuntimeException(
                'Master Referral masih kosong. Jalankan ReferralMasterSeeder terlebih dahulu.'
            );
        }

        DB::transaction(function () {
            $this->seedAdmin();
            $this->seedAccountOfficers();
            $this->seedReferrals();
        });

        $this->report();
    }

    private function seedAdmin(): void
    {
        $user = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'password' => self::DEV_ADMIN_PASSWORD,
                'role' => Role::Admin,
                'is_active' => true,
            ],
        );

        Admin::firstOrCreate(
            ['user_id' => $user->id],
            ['full_name' => 'Administrator Kebon Jeruk Multiguna'],
        );
    }

    private function seedAccountOfficers(): void
    {
        foreach ($this->accountOfficers() as $row) {
            $user = User::firstOrCreate(
                ['username' => $row['username']],
                [
                    'password' => $row['password'],
                    'role' => Role::AccountOfficer,
                    'is_active' => true,
                ],
            );

            AccountOfficer::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'full_name' => $row['full_name'],
                    'birth_date' => $row['birth_date'],
                    'email' => $row['email'],
                    'phone' => $row['phone'],
                ],
            );
        }
    }

    private function seedReferrals(): void
    {
        foreach ($this->referrals() as $row) {
            $user = User::firstOrCreate(
                ['username' => $row['username']],
                [
                    'password' => $row['password'],
                    'role' => Role::Referral,
                    'is_active' => true,
                ],
            );

            [$categoryId, $subCategoryId, $institutionId] = $this->resolveCascade(
                $row['category'],
                $row['sub_category'],
                $row['institution'],
            );

            Referral::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'full_name' => $row['full_name'],
                    'birth_date' => $row['birth_date'],
                    'email' => $row['email'],
                    'phone' => $row['phone'],
                    'category_id' => $categoryId,
                    'sub_category_id' => $subCategoryId,
                    'institution_id' => $institutionId,
                    'branch_name' => $row['branch_name'],
                ],
            );
        }
    }

    /**
     * Resolves the three-level tree by name. Names are stable in the master
     * data; generated ids are not, so never hardcode ids here.
     *
     * @return array{0: int, 1: int, 2: int|null}
     */
    private function resolveCascade(string $category, string $subCategory, ?string $institution): array
    {
        $categoryModel = ReferralCategory::where('name', $category)->first();

        if (! $categoryModel) {
            throw new RuntimeException("Kategori Referral '{$category}' tidak ditemukan.");
        }

        $subCategoryModel = $categoryModel->subCategories()->where('name', $subCategory)->first();

        if (! $subCategoryModel) {
            throw new RuntimeException(
                "Sub-kategori '{$subCategory}' tidak ditemukan pada kategori '{$category}'."
            );
        }

        $institutionId = null;

        if ($institution !== null) {
            $institutionModel = $subCategoryModel->institutions()->where('name', $institution)->first();

            if (! $institutionModel) {
                throw new RuntimeException(
                    "Instansi '{$institution}' tidak ditemukan pada sub-kategori '{$subCategory}'."
                );
            }

            $institutionId = $institutionModel->id;
        }

        return [$categoryModel->id, $subCategoryModel->id, $institutionId];
    }

    private function accountOfficers(): array
    {
        return [
            [
                'username' => 'aorahmawati',
                'password' => 'password',
                'full_name' => 'Rahmawati Dewi',
                'birth_date' => '1988-09-05',
                'email' => 'rahmawati.dewi@example.test',
                'phone' => '081234567802',
            ],
            [
                'username' => 'aosetiawan',
                'password' => 'password',
                'full_name' => 'Andi Setiawan',
                'birth_date' => '1985-03-17',
                'email' => 'andi.setiawan@example.test',
                'phone' => '081234567807',
            ],
        ];
    }

    /**
     * Three different shapes of the cascade on purpose: a sub-category with no
     * institutions, one with an institution, and an internal-staff category.
     */
    private function referrals(): array
    {
        return [
            [
                'username' => 'budisantoso',
                'password' => 'password',
                'full_name' => 'Budi Santoso',
                'birth_date' => '1990-08-12',
                'email' => 'budi.santoso@example.test',
                'phone' => '081290347712',
                'category' => 'Showroom Mobil Bekas',
                'sub_category' => 'Bursa Otomotif Sunter',
                'institution' => null,
                'branch_name' => 'Showroom Jaya Motor',
            ],
            [
                'username' => 'sitinurhaliza',
                'password' => 'password',
                'full_name' => 'Siti Nurhaliza',
                'birth_date' => '1992-11-22',
                'email' => 'siti.nurhaliza@example.test',
                'phone' => '081290347704',
                'category' => 'Captive External',
                'sub_category' => 'Area Kyai Tapa',
                'institution' => 'Jakarta Kyai Tapa',
                'branch_name' => null,
            ],
            [
                'username' => 'dedikurniawan',
                'password' => 'password',
                'full_name' => 'Dedi Kurniawan',
                'birth_date' => '1987-06-29',
                'email' => 'dedi.kurniawan@example.test',
                'phone' => '081290347709',
                'category' => 'Karyawan Internal & Captive',
                'sub_category' => 'Graha Sultan',
                'institution' => 'Team A',
                'branch_name' => null,
            ],
        ];
    }

    /**
     * Prints the credentials so a developer can actually log in. This is the
     * one place plaintext is shown, it is guarded to non-production, and the
     * values are already visible in this file. Stored form is always bcrypt.
     */
    private function report(): void
    {
        $rows = [['admin', 'Admin', self::DEV_ADMIN_PASSWORD]];

        foreach ($this->accountOfficers() as $row) {
            $rows[] = [$row['username'], 'Account Officer', $row['password']];
        }

        foreach ($this->referrals() as $row) {
            $rows[] = [$row['username'], 'Referral', $row['password']];
        }

        $this->command?->table(['Nama User', 'Role', 'Kata Sandi'], $rows);
        $this->command?->warn('Akun contoh untuk pengembangan lokal. Jangan dipakai di production.');
    }
}
