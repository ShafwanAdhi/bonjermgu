<?php

namespace Database\Factories;

use App\Domain\Application\DebtorType;
use App\Domain\Application\FinancingProduct;
use App\Domain\Application\SpouseIncomeType;
use App\Models\AccountOfficer;
use App\Models\Application;
use App\Models\Referral;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition(): array
    {
        return [
            // Left unset so the model's creating hook generates it.
            'account_officer_id' => AccountOfficer::factory(),
            'referral_id' => Referral::factory(),
            'financing_product' => FinancingProduct::DanaTunai,
            'debtor_name' => fake()->name(),
            'debtor_nik' => fake()->unique()->numerify('317305##########'),
            'debtor_birth_date' => fake()->dateTimeBetween('-55 years', '-21 years')->format('Y-m-d'),
            'debtor_type' => DebtorType::PeroranganNonWiraswasta,
            'spouse_income_type' => SpouseIncomeType::TidakAda,
            'amount_finance' => fake()->numberBetween(50, 250) * 1_000_000,
            'unit_count' => 1,
            'go_live_date' => null,
        ];
    }

    public function forOfficer(AccountOfficer $officer): static
    {
        return $this->state(fn () => ['account_officer_id' => $officer->id]);
    }

    public function forReferral(Referral $referral): static
    {
        return $this->state(fn () => ['referral_id' => $referral->id]);
    }

    /** A legal entity carries no spouse income confirmation. */
    public function legalEntity(): static
    {
        return $this->state(fn () => [
            'debtor_type' => DebtorType::BadanHukumUsaha,
            'spouse_income_type' => null,
        ]);
    }

    public function goLive(?string $date = null): static
    {
        return $this->state(fn () => ['go_live_date' => $date ?? now()->toDateString()]);
    }
}
