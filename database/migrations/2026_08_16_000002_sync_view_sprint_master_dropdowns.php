<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach (['DP20', 'DP25'] as $index => $dp) {
            DB::table('sprint_tokens')->updateOrInsert(
                ['group_key' => 'dp', 'source' => $dp],
                [
                    'product_token' => null,
                    'offering_token' => $dp,
                    'position' => 3 + $index,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }

        foreach (['DP30' => 5, 'DP40' => 6, 'DP50' => 7] as $dp => $position) {
            DB::table('sprint_tokens')
                ->where('group_key', 'dp')
                ->where('source', $dp)
                ->update(['position' => $position, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        DB::table('sprint_tokens')
            ->where('group_key', 'dp')
            ->whereIn('source', ['DP20', 'DP25'])
            ->delete();

        foreach (['DP30' => 3, 'DP40' => 4, 'DP50' => 5] as $dp => $position) {
            DB::table('sprint_tokens')
                ->where('group_key', 'dp')
                ->where('source', $dp)
                ->update(['position' => $position, 'updated_at' => now()]);
        }
    }
};
