<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('debtor_nik', 20)->nullable()->change();
            $table->date('debtor_birth_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('debtor_nik', 20)->nullable(false)->change();
            $table->date('debtor_birth_date')->nullable(false)->change();
        });
    }
};
