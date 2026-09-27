<?php

namespace Database\Factories;

use App\Enums\CreditRating;
use App\Enums\LeadStatus;
use App\Enums\LoanType;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\User;
use App\Support\StateTimeZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $state = fake()->randomElement(StateTimeZone::states());
        $propertyValue = fake()->numberBetween(120, 900) * 1000;

        return [
            'lead_source_id' => LeadSource::factory(),
            'status' => LeadStatus::New,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('##########'),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => $state,
            'zip' => fake()->numerify('#####'),
            'timezone' => StateTimeZone::for($state),
            'property_value' => $propertyValue,
            'loan_amount' => (int) round($propertyValue * fake()->randomFloat(2, 0.4, 0.95), -3),
            'loan_type' => fake()->randomElement(LoanType::cases()),
            'credit_rating' => fake()->randomElement(CreditRating::cases()),
            'yearly_income' => fake()->numberBetween(35, 220) * 1000,
            'best_time_to_call' => fake()->randomElement(['Morning', 'Afternoon', 'Evening']),
            'consent_at' => now(),
            'consent_ip' => fake()->ipv4(),
        ];
    }

    public function inState(string $state): static
    {
        return $this->state(['state' => $state, 'timezone' => StateTimeZone::for($state)]);
    }

    public function assignedTo(User $broker): static
    {
        return $this->state(['assigned_to' => $broker->id, 'status' => LeadStatus::Assigned]);
    }

    public function withStatus(LeadStatus $status): static
    {
        return $this->state(['status' => $status]);
    }
}
