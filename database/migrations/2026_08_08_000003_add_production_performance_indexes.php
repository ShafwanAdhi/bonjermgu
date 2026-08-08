<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        Schema::table('applications', function ($table) {
            $table->index(['account_officer_id', 'created_at'], 'applications_officer_created_idx');
            $table->index(['referral_id', 'created_at'], 'applications_referral_created_idx');
            $table->index(['account_officer_id', 'go_live_date'], 'applications_officer_live_idx');
            $table->index(['referral_id', 'go_live_date'], 'applications_referral_live_idx');
            $table->index(['financing_product', 'go_live_date'], 'applications_product_live_idx');
        });

        Schema::table('application_documents', function ($table) {
            $table->index(['application_id', 'status'], 'application_documents_application_status_idx');
        });

        Schema::table('application_trackings', function ($table) {
            $table->index(['application_id', 'status'], 'application_trackings_application_status_idx');
        });

        Schema::table('referrals', function ($table) {
            $table->index('category_id', 'referrals_category_idx');
            $table->index('sub_category_id', 'referrals_sub_category_idx');
            $table->index('institution_id', 'referrals_institution_idx');
        });

        Schema::table('cache', function ($table) {
            $table->index('expiration', 'cache_expiration_idx');
        });

        DB::statement(
            'CREATE INDEX applications_code_trgm_idx
             ON applications USING gin (code gin_trgm_ops)'
        );

        DB::statement(
            'CREATE INDEX applications_debtor_name_trgm_idx
             ON applications USING gin (debtor_name gin_trgm_ops)'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS applications_debtor_name_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS applications_code_trgm_idx');

        Schema::table('cache', function ($table) {
            $table->dropIndex('cache_expiration_idx');
        });

        Schema::table('referrals', function ($table) {
            $table->dropIndex('referrals_institution_idx');
            $table->dropIndex('referrals_sub_category_idx');
            $table->dropIndex('referrals_category_idx');
        });

        Schema::table('application_trackings', function ($table) {
            $table->dropIndex('application_trackings_application_status_idx');
        });

        Schema::table('application_documents', function ($table) {
            $table->dropIndex('application_documents_application_status_idx');
        });

        Schema::table('applications', function ($table) {
            $table->dropIndex('applications_product_live_idx');
            $table->dropIndex('applications_referral_live_idx');
            $table->dropIndex('applications_officer_live_idx');
            $table->dropIndex('applications_referral_created_idx');
            $table->dropIndex('applications_officer_created_idx');
        });
    }
};
