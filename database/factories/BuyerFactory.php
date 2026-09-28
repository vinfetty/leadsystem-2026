<?php

namespace Database\Factories;

use App\Enums\CreditRating;
use App\Enums\LoanType;
use App\Models\Buyer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A buyer with no rules of its own: it takes any lead until a state says otherwise.
 *
 * @extends Factory<Buyer>
 */
class BuyerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'tier' => 1,
            'price_cents' => 5000,
            'daily_cap' => null,
            'active' => true,
            'states' => null,
            'zip_prefixes' => null,
            'loan_types' => null,
            'min_loan_amount' => null,
            'max_loan_amount' => null,
            'min_credit_rating' => null,
        ];
    }

    public function tier(int $tier): static
    {
        return $this->state(['tier' => $tier]);
    }

    public function payingCents(int $cents): static
    {
        return $this->state(['price_cents' => $cents]);
    }

    public function cappedAt(int $leadsPerDay): static
    {
        return $this->state(['daily_cap' => $leadsPerDay]);
    }

    public function paused(): static
    {
        return $this->state(['active' => false]);
    }

    public function servingStates(string ...$states): static
    {
        return $this->state(['states' => $states]);
    }

    public function servingZips(string ...$prefixes): static
    {
        return $this->state(['zip_prefixes' => $prefixes]);
    }

    public function buying(LoanType ...$types): static
    {
        return $this->state(['loan_types' => array_map(fn (LoanType $type): string => $type->value, $types)]);
    }

    public function lendingBetween(?int $minimum, ?int $maximum): static
    {
        return $this->state(['min_loan_amount' => $minimum, 'max_loan_amount' => $maximum]);
    }

    public function needingCredit(CreditRating $minimum): static
    {
        return $this->state(['min_credit_rating' => $minimum]);
    }
}
