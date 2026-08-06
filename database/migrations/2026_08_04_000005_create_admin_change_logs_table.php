<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('actor_name', 150);
            $table->string('subject_type', 180);
            $table->string('subject_table', 80);
            $table->unsignedBigInteger('subject_id');
            $table->string('action', 20);
            $table->jsonb('before_values')->nullable();
            $table->jsonb('after_values')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['subject_table', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index(['actor_id', 'created_at']);
        });

        DB::statement(
            "ALTER TABLE admin_change_logs ADD CONSTRAINT admin_change_logs_action_check
             CHECK (action IN ('created', 'updated', 'deleted'))"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_change_logs');
    }
};
