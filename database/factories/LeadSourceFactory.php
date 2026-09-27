<?php

namespace Database\Factories;

use App\Models\LeadSource;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LeadSource>
 */
class LeadSourceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'code' => fake()->unique()->numerify('S###'),
            'website' => fake()->domainName(),
            'intake_token_hash' => LeadSource::hashToken(Str::random(40)),
            'active' => true,
        ];
    }

    public function withToken(string $plainToken): static
    {
        return $this->state(['intake_token_hash' => LeadSource::hashToken($plainToken)]);
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}
