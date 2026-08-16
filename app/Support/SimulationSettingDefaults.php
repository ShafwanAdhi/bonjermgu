<?php

namespace App\Support;

final class SimulationSettingDefaults
{
    /** @return array<string, string> */
    public static function values(): array
    {
        return [
            'max_vehicle_age' => '16',
            'engine_warranty_fee' => '1500000',
            'active_insurance_zone' => 'Wilayah 2',
            'active_rate_variant' => 'Batas Bawah',
            'default_deposit_instalment_count' => '0',
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

            // View Sprint hanya menampilkan; nilai ini jadi isian awal yang
            // masih bisa diubah AO sebelum diekspor.
            'view_sprint_cara_pembayaran' => 'AUTO COLLECTION',
            'view_sprint_mandiri_kpm' => 'NO',
            'view_sprint_kondisi_kendaraan' => 'USED CAR',
            'view_sprint_is_beliv' => 'TIDAK',
            'view_sprint_acp_axp' => 'ADA',
            'view_sprint_gap' => 'NO',
            'view_sprint_hic' => 'NO',
            'view_sprint_water_hammer' => 'NO',
            'view_sprint_modal_usaha_threshold' => '500000000',
        ];
    }
}
