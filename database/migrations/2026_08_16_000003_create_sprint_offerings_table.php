<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sprint_offerings', function (Blueprint $table): void {
            $table->id();
            $table->string('fingerprint', 64)->unique();
            $table->string('source_workbook', 180);
            $table->string('source_sheet', 80);
            $table->unsignedInteger('source_row');
            $table->string('source_channel', 80)->nullable();
            $table->string('product_id', 220);
            $table->string('product_offering', 260);
            $table->string('product_category', 80)->nullable();
            $table->string('channel', 80)->nullable();
            $table->string('region', 30)->nullable();
            $table->string('unit', 30)->nullable();
            $table->string('brand', 30)->nullable();
            $table->string('profile', 40)->nullable();
            $table->string('debtor_type', 40)->nullable();
            $table->string('dp', 10)->nullable();
            $table->string('tenor', 5)->nullable();
            $table->string('instalment', 5)->nullable();
            $table->timestamps();

            $table->index(['product_category', 'channel', 'region']);
            $table->index(['unit', 'brand', 'profile']);
            $table->index(['dp', 'tenor', 'instalment']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sprint_offerings');
    }
};
