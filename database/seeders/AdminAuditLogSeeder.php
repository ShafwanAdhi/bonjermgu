<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Admin;
use App\Models\AdminChangeLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Local-only sample audit rows for reviewing /configuration/audit.
 *
 * The real audit table is append-only from Admin actions. This seeder only
 * creates deterministic review data outside production and never deletes
 * existing audit history.
 */
class AdminAuditLogSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('AdminAuditLogSeeder dilewati: data contoh audit tidak dibuat di production.');

            return;
        }

        DB::transaction(function () {
            $actor = $this->actor();

            foreach ($this->rows($actor) as $row) {
                AdminChangeLog::query()->updateOrCreate(
                    [
                        'actor_id' => $actor->id,
                        'audit_module' => $row['audit_module'],
                        'subject_table' => $row['subject_table'],
                        'subject_id' => $row['subject_id'],
                        'action' => $row['action'],
                    ],
                    $row,
                );
            }
        });
    }

    private function actor(): User
    {
        $user = User::query()->firstOrCreate(
            ['username' => 'audit.demo'],
            [
                'password' => 'password',
                'role' => Role::Admin,
                'is_active' => true,
            ],
        );

        Admin::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'full_name' => 'Admin Audit Demo',
                'email' => 'audit.demo@example.test',
            ],
        );

        return $user;
    }

    private function rows(User $actor): array
    {
        $rows = [
            [
                'actor_id' => $actor->id,
                'actor_name' => $actor->username,
                'audit_module' => 'master.vehicles',
                'subject_type' => 'App\\Models\\VehicleModel',
                'subject_table' => 'vehicle_models',
                'subject_id' => 4679,
                'action' => 'created',
                'before_values' => null,
                'after_values' => [
                    'id' => 4679,
                    'name' => 'All New HR-V RS e:HEV Long Name Untuk Review Mobile',
                    'type_id' => 269,
                    'description' => 'Contoh teks panjang agar card audit mobile tidak pecah atau terpotong.',
                ],
                'created_at' => now()->subHours(2),
            ],
            [
                'actor_id' => $actor->id,
                'actor_name' => $actor->username,
                'audit_module' => 'configuration.products',
                'subject_type' => 'App\\Models\\Product',
                'subject_table' => 'products',
                'subject_id' => 22,
                'action' => 'updated',
                'before_values' => [
                    'name' => 'Reguler Passenger Referral',
                    'admin_min' => 3750000,
                    'admin_max' => 5350000,
                    'is_active' => true,
                ],
                'after_values' => [
                    'name' => 'Reguler Passenger Referral',
                    'admin_min' => 3900000,
                    'admin_max' => 5500000,
                    'is_active' => true,
                ],
                'created_at' => now()->subDay()->setTime(16, 35),
            ],
            [
                'actor_id' => $actor->id,
                'actor_name' => $actor->username,
                'audit_module' => 'configuration.insurance',
                'subject_type' => 'App\\Models\\InsuranceExtensionRate',
                'subject_table' => 'insurance_extension_rates',
                'subject_id' => 19,
                'action' => 'deleted',
                'before_values' => [
                    'region' => 'Jabodetabek',
                    'variant' => 'Comprehensive dengan perluasan banjir dan huru hara',
                    'rate' => '0.0035',
                ],
                'after_values' => null,
                'created_at' => now()->subDays(2)->setTime(9, 10),
            ],
        ];

        foreach (range(1, 24) as $index) {
            $rows[] = [
                'actor_id' => $actor->id,
                'actor_name' => $actor->username,
                'audit_module' => $index % 2 === 0 ? 'configuration.fees' : 'master.vehicles',
                'subject_type' => $index % 2 === 0 ? 'App\\Models\\SimulationSetting' : 'App\\Models\\VehicleVariant',
                'subject_table' => $index % 2 === 0 ? 'simulation_settings' : 'vehicle_variants',
                'subject_id' => 4800 + $index,
                'action' => $index % 3 === 0 ? 'created' : 'updated',
                'before_values' => $index % 3 === 0 ? null : [
                    'name' => "Data audit demo {$index}",
                    'value' => 1000000 + ($index * 25000),
                ],
                'after_values' => [
                    'name' => "Data audit demo {$index}",
                    'value' => 1250000 + ($index * 25000),
                    'note' => 'Baris contoh untuk memvalidasi navigasi halaman audit.',
                ],
                'created_at' => now()->subDays($index + 3)->setTime(10 + ($index % 8), 15),
            ];
        }

        return $rows;
    }
}
