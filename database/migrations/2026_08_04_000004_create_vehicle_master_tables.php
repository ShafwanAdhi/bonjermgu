<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_usages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20)->unique();
        });

        Schema::create('vehicle_brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usage_id')->constrained('vehicle_usages')->restrictOnDelete();
            $table->string('name', 100);
            $table->string('origin', 20);

            $table->unique(['usage_id', 'name']);
        });

        Schema::create('vehicle_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('vehicle_brands')->restrictOnDelete();
            $table->string('name', 100);

            $table->unique(['brand_id', 'name']);
        });

        Schema::create('vehicle_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_id')->constrained('vehicle_types')->restrictOnDelete();
            $table->string('name', 150);

            $table->unique(['type_id', 'name']);
        });

        Schema::create('vehicle_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_id')->constrained('vehicle_models')->restrictOnDelete();
            $table->smallInteger('year');
            $table->unsignedBigInteger('price');

            $table->unique(['model_id', 'year']);
            $table->index(['year', 'price']);
        });

        DB::statement("ALTER TABLE vehicle_usages ADD CONSTRAINT vehicle_usages_name_check CHECK (name IN ('Passenger', 'Commercial'))");
        DB::statement("ALTER TABLE vehicle_brands ADD CONSTRAINT vehicle_brands_origin_check CHECK (origin IN ('Japan', 'Non Japan'))");
        DB::statement('ALTER TABLE vehicle_prices ADD CONSTRAINT vehicle_prices_year_check CHECK (year > 0)');
        DB::statement('ALTER TABLE vehicle_prices ADD CONSTRAINT vehicle_prices_price_check CHECK (price >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_prices');
        Schema::dropIfExists('vehicle_models');
        Schema::dropIfExists('vehicle_types');
        Schema::dropIfExists('vehicle_brands');
        Schema::dropIfExists('vehicle_usages');
    }
};
