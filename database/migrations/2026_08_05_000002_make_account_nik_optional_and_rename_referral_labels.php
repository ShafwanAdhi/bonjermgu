<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->string('nik', 20)->nullable()->change();
        });

        Schema::table('account_officers', function (Blueprint $table) {
            $table->string('nik', 20)->nullable()->change();
        });

        DB::table('referral_categories')
            ->whereIn('name', ['Karyawan Instansi & BUMN', 'Karyawan Instansi', 'Karyawan Internal'])
            ->update(['name' => 'Karyawan Internal & Captive']);

        DB::table('referral_sub_categories')
            ->whereIn('name', ['Sales Dealer Mobil Bekas', 'Karyawan Authorized Dealer'])
            ->update(['name' => 'Sales Authorized Dealer']);

        DB::table('referral_sub_categories')
            ->whereIn('name', ['Agen & Broker Asuransi', 'Agen & broker asuransi', 'Karyawan Asuransi Rekanan'])
            ->update(['name' => 'Karyawan asuransi Rekanan']);
    }

    public function down(): void
    {
        DB::table('referral_sub_categories')
            ->where('name', 'Karyawan asuransi Rekanan')
            ->update(['name' => 'Karyawan Asuransi Rekanan']);

        DB::table('referral_sub_categories')
            ->where('name', 'Sales Authorized Dealer')
            ->update(['name' => 'Karyawan Authorized Dealer']);

        DB::table('referral_categories')
            ->where('name', 'Karyawan Internal & Captive')
            ->update(['name' => 'Karyawan Internal']);

        foreach (DB::table('referrals')->whereNull('nik')->pluck('id') as $id) {
            DB::table('referrals')
                ->where('id', $id)
                ->update(['nik' => 'LEGACYR'.str_pad((string) $id, 13, '0', STR_PAD_LEFT)]);
        }

        foreach (DB::table('account_officers')->whereNull('nik')->pluck('id') as $id) {
            DB::table('account_officers')
                ->where('id', $id)
                ->update(['nik' => 'LEGACYO'.str_pad((string) $id, 13, '0', STR_PAD_LEFT)]);
        }

        Schema::table('referrals', function (Blueprint $table) {
            $table->string('nik', 20)->nullable(false)->change();
        });

        Schema::table('account_officers', function (Blueprint $table) {
            $table->string('nik', 20)->nullable(false)->change();
        });
    }
};
