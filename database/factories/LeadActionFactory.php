<?php

namespace Database\Factories;

use App\Enums\LeadActionType;
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
            'type' => fake()->randomElement([LeadActionType::Called, LeadActionType::LeftMessage, LeadActionType::Note]),
            'note' => fake()->randomElement([
                'Wants figures for a 15-year term.',
                'Comparing with another lender, deciding by Friday.',
                'Asked for the rate sheet by email.',
                'No answer on the mobile, tried the home number.',
                'Spouse needs to be on the next call.',
                'Appraisal came in lower than expected.',
                'Prefers a call after 5 pm.',
            ]),
        ];
    }

    /**
     * The entry written when the lead first arrived.
     */
    public function received(Lead $lead): static
    {
        return $this->for($lead)->state([
            'type' => LeadActionType::Received,
            'note' => 'Received from '.$lead->source->name,
            'created_at' => $lead->created_at,
        ]);
    }

    /**
     * The entry written when the lead's current call-back was booked.
     */
    public function scheduled(Lead $lead): static
    {
        return $this->for($lead)->state([
            'user_id' => $lead->assigned_to,
            'type' => LeadActionType::Scheduled,
            'note' => 'Call back '.$lead->follow_up_at->setTimezone($lead->timezone)->format('D j M Y, g:i a T'),
            'created_at' => $lead->created_at->addHour(),
        ]);
    }
}
