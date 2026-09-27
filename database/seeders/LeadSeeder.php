<?php

namespace Database\Seeders;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\LeadAction;
use App\Models\LeadSource;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Fill the demo with invented leads. Nothing here comes from a real person.
 */
class LeadSeeder extends Seeder
{
    public function run(): void
    {
        $brokers = User::factory()->count(3)->create();

        $sources = collect([
            ['name' => 'Home Loan Finder', 'code' => 'S101', 'website' => 'homeloanfinder.example'],
            ['name' => 'Refinance Today', 'code' => 'S102', 'website' => 'refinancetoday.example'],
            ['name' => 'Partner Network', 'code' => 'P201', 'website' => 'partnernetwork.example'],
        ])->map(fn (array $source): LeadSource => LeadSource::factory()->create($source));

        foreach ($sources as $source) {
            Lead::factory()->count(12)->recycle($source)->create();

            foreach ($brokers as $broker) {
                Lead::factory()
                    ->count(4)
                    ->recycle($source)
                    ->assignedTo($broker)
                    ->has(LeadAction::factory()->count(2)->state(['user_id' => $broker->id]), 'actions')
                    ->create();

                Lead::factory()
                    ->count(2)
                    ->recycle($source)
                    ->assignedTo($broker)
                    ->withStatus(LeadStatus::Scheduled)
                    ->state(fn (): array => ['follow_up_at' => now()->addDays(fake()->numberBetween(1, 7))])
                    ->create();
            }
        }
    }
}
