<?php

namespace Database\Seeders;

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
        $brokers = User::query()->brokers()->get();

        $sources = collect([
            ['name' => 'Home Loan Finder', 'code' => 'S101', 'website' => 'homeloanfinder.example'],
            ['name' => 'Refinance Today', 'code' => 'S102', 'website' => 'refinancetoday.example'],
            ['name' => 'Partner Network', 'code' => 'P201', 'website' => 'partnernetwork.example'],
        ])->map(fn (array $source): LeadSource => LeadSource::factory()->create($source));

        foreach ($sources as $source) {
            Lead::factory()
                ->count(12)
                ->recycle($source)
                ->receivedWithinDays(4)
                ->create();

            foreach ($brokers as $broker) {
                Lead::factory()
                    ->count(4)
                    ->recycle($source)
                    ->assignedTo($broker)
                    ->has(LeadAction::factory()->count(2)->state(['user_id' => $broker->id, 'created_at' => now()]), 'actions')
                    ->receivedWithinDays(4)
                    ->create();

                Lead::factory()
                    ->count(2)
                    ->recycle($source)
                    ->assignedTo($broker)
                    ->callbackWithinDays(-1, 5)
                    ->receivedWithinDays(4)
                    ->create();
            }
        }

        Lead::query()->with('source')->each(function (Lead $lead): void {
            LeadAction::factory()->received($lead)->create();

            if ($lead->follow_up_at !== null) {
                LeadAction::factory()->scheduled($lead)->create();
            }
        });
    }
}
