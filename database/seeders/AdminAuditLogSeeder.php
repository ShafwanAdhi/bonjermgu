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
        return [
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
    }
}
