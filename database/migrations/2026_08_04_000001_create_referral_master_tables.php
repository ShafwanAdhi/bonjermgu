<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Master Referral: the three-level cascade behind the registration form,
 * plus the two simple lookups used by Credit Simulation.
 *
 * `segment` and `tier` on a category are what build the Product name —
 * see docs/credit-simulation.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('code', 10)->unique();
            $table->string('segment', 20);
            $table->string('tier', 100);
            $table->boolean('is_active')->default(true);
        });

        DB::statement(
            "ALTER TABLE referral_categories ADD CONSTRAINT referral_categories_segment_check
             CHECK (segment IN ('Reguler', 'Captive'))"
        );

        Schema::create('referral_sub_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('referral_categories');
            $table->string('name', 100);

            $table->unique(['category_id', 'name']);
        });

        // For Captive categories this third level holds branch names rather than
        // institutions — see docs/data-model.md section 4.
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_category_id')->constrained('referral_sub_categories');
            $table->string('name', 150);

            $table->unique(['sub_category_id', 'name']);
        });

        Schema::create('domiciles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->smallInteger('sort_order');
        });

        Schema::create('age_groups', function (Blueprint $table) {
            $table->id();
            $table->string('label', 30)->unique();
            $table->smallInteger('sort_order');
        });

        DB::statement(
            'ALTER TABLE domiciles ADD CONSTRAINT domiciles_sort_order_check CHECK (sort_order >= 1)'
        );
        DB::statement(
            'ALTER TABLE age_groups ADD CONSTRAINT age_groups_sort_order_check CHECK (sort_order >= 1)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('age_groups');
        Schema::dropIfExists('domiciles');
        Schema::dropIfExists('institutions');
        Schema::dropIfExists('referral_sub_categories');
        Schema::dropIfExists('referral_categories');
    }
};
