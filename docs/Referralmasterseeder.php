<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the referral master: categories, sub-categories, and institutions,
 * plus the domicile and age group lookups used by Credit Simulation.
 *
 * Source: draft_Web_bonjermgu_agt_2026_2.xlsx, sheet "Data Ext".
 * See docs/master-data-extraction.md for the extraction rules.
 *
 * The `segment` and `tier` columns on a category are what build the Product
 * name during simulation. See docs/credit-simulation.md.
 *
 * Idempotent: safe to run repeatedly.
 */
class ReferralMasterSeeder extends Seeder
{
    public function run(): void
    {
        $master = json_decode(
            file_get_contents(database_path('seeders/data/referral_master.json')),
            true,
        );

        DB::transaction(function () use ($master) {
            $categories = 0;
            $subCategories = 0;
            $institutions = 0;

            foreach ($master['categories'] as $category) {
                DB::table('referral_categories')->upsert(
                    [[
                        'name' => $category['name'],
                        'code' => $category['code'],
                        'segment' => $category['segment'],
                        'tier' => $category['tier'],
                        'is_active' => true,
                    ]],
                    ['name'],
                    ['code', 'segment', 'tier'],
                );

                $categoryId = DB::table('referral_categories')
                    ->where('name', $category['name'])->value('id');
                $categories++;

                foreach ($category['sub_categories'] as $sub) {
                    DB::table('referral_sub_categories')->upsert(
                        [['category_id' => $categoryId, 'name' => $sub['name']]],
                        ['category_id', 'name'],
                        [],
                    );

                    $subId = DB::table('referral_sub_categories')
                        ->where('category_id', $categoryId)
                        ->where('name', $sub['name'])
                        ->value('id');
                    $subCategories++;

                    foreach ($sub['institutions'] as $institution) {
                        DB::table('institutions')->upsert(
                            [['sub_category_id' => $subId, 'name' => $institution]],
                            ['sub_category_id', 'name'],
                            [],
                        );
                        $institutions++;
                    }
                }
            }

            foreach ($master['domiciles'] as $index => $name) {
                DB::table('domiciles')->upsert(
                    [['name' => $name, 'sort_order' => $index + 1]],
                    ['name'],
                    ['sort_order'],
                );
            }

            foreach ($master['age_groups'] as $index => $label) {
                DB::table('age_groups')->upsert(
                    [['label' => $label, 'sort_order' => $index + 1]],
                    ['label'],
                    ['sort_order'],
                );
            }

            $this->command->info(sprintf(
                'Referral master: %d categories, %d sub-categories, %d institutions, %d domiciles, %d age groups.',
                $categories,
                $subCategories,
                $institutions,
                count($master['domiciles']),
                count($master['age_groups']),
            ));
        });
    }
}
