<?php

use App\Domain\Application\ApplicationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('application_status', 20)
                ->default(ApplicationStatus::Pipeline->value)
                ->after('go_live_date');

            $table->index('application_status');
        });

        DB::statement(
            "ALTER TABLE applications ADD CONSTRAINT applications_status_check
             CHECK (application_status IN ('Pipe Line', 'Canceled'))"
        );
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE applications DROP CONSTRAINT IF EXISTS applications_status_check');

        Schema::table('applications', function (Blueprint $table) {
            $table->dropIndex(['application_status']);
            $table->dropColumn('application_status');
        });
    }
};
