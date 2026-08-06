<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\AccountOfficer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountOfficer>
 */
class AccountOfficerFactory extends Factory
{
    protected $model = AccountOfficer::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => Role::AccountOfficer]),
            'full_name' => fake()->name(),
            'birth_date' => fake()->dateTimeBetween('-55 years', '-24 years')->format('Y-m-d'),
            'nik' => null,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('08##########'),
        ];
    }
}
