<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One profile row per user, keyed by role. The four referral identity
 * attributes are relational columns — never one concatenated string.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('full_name', 150);
            $table->timestamps();
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('full_name', 150);
            $table->date('birth_date');
            $table->string('nik', 20)->nullable()->unique();
            $table->string('email', 150)->nullable();
            $table->string('phone', 20)->nullable();
            $table->foreignId('category_id')->constrained('referral_categories');
            $table->foreignId('sub_category_id')->constrained('referral_sub_categories');
            $table->foreignId('institution_id')->nullable()->constrained('institutions');
            $table->string('branch_name', 150)->nullable();
            $table->timestamps();
        });

        Schema::create('account_officers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('full_name', 150);
            $table->date('birth_date');
            $table->string('nik', 20)->nullable()->unique();
            $table->string('email', 150)->nullable();
            $table->string('phone', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_officers');
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('admins');
    }
};
