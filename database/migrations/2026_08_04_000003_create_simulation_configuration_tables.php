<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->decimal('dp_rate', 6, 4);
            $table->unsignedBigInteger('admin_min');
            $table->unsignedBigInteger('admin_max');
            $table->decimal('provisi_rate', 6, 4)->default(0);
            $table->decimal('up_acp', 6, 4)->default(0);
            $table->decimal('up_rate', 6, 4)->default(0);
            $table->unsignedBigInteger('up_admin')->default(0);
            $table->decimal('up_provisi', 6, 4)->default(0);
            $table->boolean('is_active')->default(true);
        });

        Schema::create('product_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->smallInteger('tenor_months');
            $table->decimal('effective_rate', 12, 10)->nullable();

            $table->unique(['product_id', 'tenor_months']);
        });

        Schema::create('insurance_casco_rates', function (Blueprint $table) {
            $table->id();
            $table->string('zone', 30);
            $table->string('usage', 20);
            $table->string('variant', 20);
            $table->string('coverage', 20);
            $table->unsignedBigInteger('band_min');
            $table->unsignedBigInteger('band_max')->nullable();
            $table->decimal('rate', 12, 10);

            $table->unique(
                ['zone', 'usage', 'variant', 'coverage', 'band_min'],
                'insurance_casco_rates_lookup_unique',
            );
        });

        Schema::create('insurance_loading_rates', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('vehicle_age')->unique();
            $table->decimal('rate', 12, 10);
        });

        Schema::create('insurance_extension_rates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->decimal('rate', 12, 10);
        });

        Schema::create('acp_base_rates', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('tenor_years')->unique();
            $table->decimal('rate', 12, 10);
        });

        Schema::create('acp_uppings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('age_group_id')->unique()->constrained()->restrictOnDelete();
            $table->decimal('upping', 6, 4);
        });

        Schema::create('tjh_tiers', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('sequence')->unique();
            $table->unsignedBigInteger('limit_amount')->nullable();
            $table->decimal('rate', 12, 10);
        });

        Schema::create('fiducia_tiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('min_amount')->unique();
            $table->unsignedBigInteger('max_amount')->nullable();
            $table->unsignedBigInteger('fee');
        });

        Schema::create('sum_insured_schedules', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('year_index')->unique();
            $table->decimal('percentage', 6, 4);
        });

        Schema::create('simulation_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();
            $table->string('value', 100);
        });

        $this->addChecks();
    }

    private function addChecks(): void
    {
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_dp_rate_check CHECK (dp_rate BETWEEN 0 AND 1)');
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_admin_nonnegative_check CHECK (admin_min >= 0 AND admin_max >= 0 AND up_admin >= 0)');
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_admin_range_check CHECK (admin_min <= admin_max)');
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_provisi_rate_check CHECK (provisi_rate BETWEEN 0 AND 1)');
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_up_acp_check CHECK (up_acp BETWEEN 0 AND 1)');
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_up_rate_check CHECK (up_rate BETWEEN 0 AND 1)');
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_up_provisi_check CHECK (up_provisi BETWEEN 0 AND 1)');

        DB::statement('ALTER TABLE product_rates ADD CONSTRAINT product_rates_tenor_check CHECK (tenor_months IN (12, 24, 36, 48, 60))');
        DB::statement('ALTER TABLE product_rates ADD CONSTRAINT product_rates_effective_rate_check CHECK (effective_rate IS NULL OR effective_rate BETWEEN 0 AND 1)');

        DB::statement("ALTER TABLE insurance_casco_rates ADD CONSTRAINT insurance_casco_rates_usage_check CHECK (usage IN ('Passenger', 'Commercial'))");
        DB::statement("ALTER TABLE insurance_casco_rates ADD CONSTRAINT insurance_casco_rates_variant_check CHECK (variant IN ('Batas Atas', 'Batas Bawah'))");
        DB::statement("ALTER TABLE insurance_casco_rates ADD CONSTRAINT insurance_casco_rates_coverage_check CHECK (coverage IN ('Comprehensive', 'TLO'))");
        DB::statement('ALTER TABLE insurance_casco_rates ADD CONSTRAINT insurance_casco_rates_band_min_check CHECK (band_min >= 0)');
        DB::statement('ALTER TABLE insurance_casco_rates ADD CONSTRAINT insurance_casco_rates_band_check CHECK (band_max IS NULL OR band_max >= band_min)');
        DB::statement('ALTER TABLE insurance_casco_rates ADD CONSTRAINT insurance_casco_rates_rate_check CHECK (rate BETWEEN 0 AND 1)');

        DB::statement('ALTER TABLE insurance_loading_rates ADD CONSTRAINT insurance_loading_rates_age_check CHECK (vehicle_age >= 0)');
        DB::statement('ALTER TABLE insurance_loading_rates ADD CONSTRAINT insurance_loading_rates_rate_check CHECK (rate BETWEEN 0 AND 1)');

        DB::statement("ALTER TABLE insurance_extension_rates ADD CONSTRAINT insurance_extension_rates_code_check CHECK (code IN ('banjir', 'gempa', 'huru_hara', 'teroris', 'pengemudi', 'penumpang'))");
        DB::statement('ALTER TABLE insurance_extension_rates ADD CONSTRAINT insurance_extension_rates_rate_check CHECK (rate BETWEEN 0 AND 1)');

        DB::statement('ALTER TABLE acp_base_rates ADD CONSTRAINT acp_base_rates_tenor_check CHECK (tenor_years BETWEEN 1 AND 5)');
        DB::statement('ALTER TABLE acp_base_rates ADD CONSTRAINT acp_base_rates_rate_check CHECK (rate BETWEEN 0 AND 1)');
        DB::statement('ALTER TABLE acp_uppings ADD CONSTRAINT acp_uppings_value_check CHECK (upping BETWEEN 0 AND 1)');

        DB::statement('ALTER TABLE tjh_tiers ADD CONSTRAINT tjh_tiers_sequence_check CHECK (sequence >= 1)');
        DB::statement('ALTER TABLE tjh_tiers ADD CONSTRAINT tjh_tiers_limit_check CHECK (limit_amount IS NULL OR limit_amount > 0)');
        DB::statement('ALTER TABLE tjh_tiers ADD CONSTRAINT tjh_tiers_rate_check CHECK (rate BETWEEN 0 AND 1)');

        DB::statement('ALTER TABLE fiducia_tiers ADD CONSTRAINT fiducia_tiers_nonnegative_check CHECK (min_amount >= 0 AND fee >= 0)');
        DB::statement('ALTER TABLE fiducia_tiers ADD CONSTRAINT fiducia_tiers_range_check CHECK (max_amount IS NULL OR max_amount >= min_amount)');
        DB::statement('ALTER TABLE sum_insured_schedules ADD CONSTRAINT sum_insured_schedules_year_check CHECK (year_index BETWEEN 1 AND 5)');
        DB::statement('ALTER TABLE sum_insured_schedules ADD CONSTRAINT sum_insured_schedules_percentage_check CHECK (percentage BETWEEN 0 AND 1)');
    }

    public function down(): void
    {
        Schema::dropIfExists('simulation_settings');
        Schema::dropIfExists('sum_insured_schedules');
        Schema::dropIfExists('fiducia_tiers');
        Schema::dropIfExists('tjh_tiers');
        Schema::dropIfExists('acp_uppings');
        Schema::dropIfExists('acp_base_rates');
        Schema::dropIfExists('insurance_extension_rates');
        Schema::dropIfExists('insurance_loading_rates');
        Schema::dropIfExists('insurance_casco_rates');
        Schema::dropIfExists('product_rates');
        Schema::dropIfExists('products');
    }
};
