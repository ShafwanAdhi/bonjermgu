<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            'max_vehicle_age' => '16',
            'engine_warranty_fee' => '1500000',
            'active_insurance_zone' => 'Wilayah 2',
            'active_rate_variant' => 'Batas Bawah',
            'default_deposit_instalment_amount' => '0',
            'default_bbnkb_amount' => '0',
            'default_pkb_amount' => '0',
            'default_invoice_amount' => '0',
            'default_flood_enabled' => 'false',
            'default_earthquake_enabled' => 'false',
            'default_riot_enabled' => 'false',
            'default_terrorism_enabled' => 'false',
            'default_tjh_amount' => '0',
            'default_driver_coverage_amount' => '0',
            'default_passenger_coverage_amount' => '0',
            'default_passenger_count' => '0',
            'default_engine_warranty_enabled' => 'true',
            'tjh_max_amount' => '50000000',
            'tjh_step_amount' => '5000000',
            'dtn_standard_net_dp_rate' => '0.0500',
            'dtn_high_risk_net_dp_rate' => '0.1500',
            'ucf_standard_net_dp_rate' => '0.1000',
            'ucf_non_japan_net_dp_rate' => '0.1500',
            'ucf_entrepreneur_net_dp_rate' => '0.3000',
            'dtn_acp_enabled' => 'true',
            'ucf_acp_enabled' => 'true',
            'acp_max_loan_amount' => '1000000000',
            'ucf_insurance_refund_base_rate' => '0.1000',
            'ucf_insurance_refund_rate' => '1.0000',
            'ucf_interest_refund_rate' => '0.8000',
            'ucf_provision_refund_rate' => '0.8000',
            'ucf_admin_refund_rate' => '0.8000',
        ];

        DB::table('simulation_settings')->insertOrIgnore(
            array_map(
                fn (string $key, string $value): array => compact('key', 'value'),
                array_keys($settings),
                array_values($settings),
            ),
        );
    }

    public function down(): void
    {
        //
    }
};
