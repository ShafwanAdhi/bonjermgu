<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            // The model's `hashed` cast handles hashing. Tests that need a
            // known password pass it explicitly.
            'password' => 'password',
            'role' => Role::Referral,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => Role::Admin]);
    }

    public function referral(): static
    {
        return $this->state(fn () => ['role' => Role::Referral]);
    }

    public function accountOfficer(): static
    {
        return $this->state(fn () => ['role' => Role::AccountOfficer]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
