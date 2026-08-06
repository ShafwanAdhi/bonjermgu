<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ReferralMasterSeeder::class,
            // Depends on age groups created by ReferralMasterSeeder.
            SimulationConfigurationSeeder::class,
            VehicleSeeder::class,
            // Fixed catalogues. Neither is editable through the interface.
            DocumentRequirementSeeder::class,
            TrackingStageSeeder::class,
            // Depends on the master data above. Skips itself in production.
            UserSeeder::class,
            // Depends on the seeded AO, Referral, document catalogue, and stages.
            ApplicationSeeder::class,
        ]);
    }
}
