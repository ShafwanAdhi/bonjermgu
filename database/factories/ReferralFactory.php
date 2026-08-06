<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Institution;
use App\Models\Referral;
use App\Models\ReferralCategory;
use App\Models\ReferralSubCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Referral>
 */
class ReferralFactory extends Factory
{
    protected $model = Referral::class;

    public function definition(): array
    {
        // Build the cascade so the profile always points at a tree that exists.
        $category = ReferralCategory::query()->inRandomOrder()->first()
            ?? ReferralCategory::create([
                'name' => fake()->unique()->company(),
                'code' => strtoupper(fake()->unique()->lexify('???')),
                'segment' => 'Reguler',
                'tier' => 'Referral',
                'allows_passenger' => true,
                'allows_commercial' => true,
                'is_active' => true,
            ]);

        $subCategory = $category->subCategories()->inRandomOrder()->first()
            ?? ReferralSubCategory::create([
                'category_id' => $category->id,
                'name' => fake()->unique()->citySuffix().' '.fake()->unique()->word(),
            ]);

        return [
            'user_id' => User::factory()->state(['role' => Role::Referral]),
            'full_name' => fake()->name(),
            'birth_date' => fake()->dateTimeBetween('-55 years', '-21 years')->format('Y-m-d'),
            'nik' => null,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('08##########'),
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'institution_id' => null,
            'branch_name' => null,
        ];
    }

    /** Attaches an institution under the referral's own sub-category. */
    public function withInstitution(): static
    {
        return $this->afterMaking(function (Referral $referral) {
            $institution = Institution::firstOrCreate([
                'sub_category_id' => $referral->sub_category_id,
                'name' => fake()->unique()->company(),
            ]);

            $referral->institution_id = $institution->id;
        });
    }
}
