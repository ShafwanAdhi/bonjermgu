<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_change_logs', function (Blueprint $table) {
            $table->string('audit_module', 80)->nullable()->after('actor_name');
            $table->index(['audit_module', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('admin_change_logs', function (Blueprint $table) {
            $table->dropIndex(['audit_module', 'created_at']);
            $table->dropColumn('audit_module');
        });
    }
};
