<?php

namespace Database\Factories;

use App\Models\Buyer;
use App\Models\Lead;
use App\Models\RoutingAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A lead delivered to a buyer.
 *
 * @extends Factory<RoutingAttempt>
 */
class RoutingAttemptFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'buyer_id' => Buyer::factory(),
            'price_cents' => 5000,
            'evaluations' => [],
        ];
    }

    public function rejected(): static
    {
        return $this->state(['buyer_id' => null, 'price_cents' => null]);
    }
}
