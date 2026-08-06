<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Credit Application and the two status groups that hang off it.
 *
 * Constraints that can be expressed in the database are expressed here.
 * Application validation is an extra layer, never the only one
 * (docs/data-model.md section 1).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Catalogue of 26 requirements. Primary key is the stable code, not a
        // serial — document status rows point at it and it must never change.
        Schema::create('document_requirements', function (Blueprint $table) {
            $table->string('code', 20)->primary();
            $table->string('name', 150);
            $table->string('subject', 20);
            $table->string('group_name', 30);
            $table->smallInteger('sort_order');

            $table->unique(['group_name', 'sort_order']);
        });

        DB::statement(
            "ALTER TABLE document_requirements ADD CONSTRAINT document_requirements_subject_check
             CHECK (subject IN ('Pemohon', 'Pasangan', 'Komisaris', 'Direksi', 'Badan Usaha'))"
        );

        DB::statement(
            "ALTER TABLE document_requirements ADD CONSTRAINT document_requirements_group_check
             CHECK (group_name IN ('Perorangan', 'Badan Hukum Usaha', 'Pasangan'))"
        );

        // Fixed content: eleven rows, not configurable through any interface.
        Schema::create('tracking_stages', function (Blueprint $table) {
            $table->smallInteger('stage_no')->primary();
            $table->string('name', 150);
        });

        DB::statement(
            'ALTER TABLE tracking_stages ADD CONSTRAINT tracking_stages_range_check
             CHECK (stage_no BETWEEN 1 AND 11)'
        );

        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('code', 6)->unique();
            $table->foreignId('account_officer_id')->constrained('account_officers');
            $table->foreignId('referral_id')->constrained('referrals');
            $table->string('financing_product', 3);
            $table->string('debtor_name', 150);
            $table->string('debtor_nik', 20);
            $table->date('debtor_birth_date');
            $table->string('debtor_type', 40);
            $table->string('spouse_income_type', 80)->nullable();
            $table->bigInteger('amount_finance')->nullable();
            $table->smallInteger('unit_count')->default(1);
            $table->date('go_live_date')->nullable();
            $table->timestamps();

            $table->index('account_officer_id');
            $table->index('referral_id');
            $table->index('go_live_date');
            $table->index('financing_product');
        });

        DB::statement(
            "ALTER TABLE applications ADD CONSTRAINT applications_product_check
             CHECK (financing_product IN ('DTN', 'UCF'))"
        );

        DB::statement(
            "ALTER TABLE applications ADD CONSTRAINT applications_debtor_type_check
             CHECK (debtor_type IN ('Perorangan Non Wiraswasta', 'Perorangan Wiraswasta', 'Badan Hukum Usaha'))"
        );

        DB::statement(
            'ALTER TABLE applications ADD CONSTRAINT applications_amount_finance_check
             CHECK (amount_finance IS NULL OR amount_finance >= 0)'
        );

        DB::statement(
            'ALTER TABLE applications ADD CONSTRAINT applications_unit_count_check
             CHECK (unit_count >= 1)'
        );

        // A legal entity has no spouse documents, so the determinant must be
        // NULL there — data-model.md section 6.
        DB::statement(
            "ALTER TABLE applications ADD CONSTRAINT applications_spouse_income_check
             CHECK (
                 (debtor_type = 'Badan Hukum Usaha' AND spouse_income_type IS NULL)
                 OR (debtor_type <> 'Badan Hukum Usaha' AND spouse_income_type IS NOT NULL)
             )"
        );

        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('requirement_code', 20);
            $table->string('status', 10);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('requirement_code')->references('code')->on('document_requirements');
            $table->unique(['application_id', 'requirement_code']);
        });

        DB::statement(
            "ALTER TABLE application_documents ADD CONSTRAINT application_documents_status_check
             CHECK (status IN ('Belum', 'Lengkap'))"
        );

        Schema::create('application_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('stage_no');
            $table->string('status', 10);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('stage_no')->references('stage_no')->on('tracking_stages');
            $table->unique(['application_id', 'stage_no']);
        });

        DB::statement(
            "ALTER TABLE application_trackings ADD CONSTRAINT application_trackings_status_check
             CHECK (status IN ('Belum', 'Selesai'))"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('application_trackings');
        Schema::dropIfExists('application_documents');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('tracking_stages');
        Schema::dropIfExists('document_requirements');
    }
};
