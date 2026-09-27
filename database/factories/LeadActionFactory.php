<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\LeadAction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadAction>
 */
class LeadActionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'type' => fake()->randomElement(['called', 'left_message', 'note']),
            'note' => fake()->sentence(),
        ];
    }
}
