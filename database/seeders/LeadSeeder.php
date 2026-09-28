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
    /**
     * Each demo source accepts leads with this prefix followed by its code,
     * for example "demo-token-S101". Real tokens are random and never stored.
     */
    public const DEMO_TOKEN_PREFIX = 'demo-token-';

    public function run(): void
    {
        $processors = User::query()->processors()->get();

        $sources = collect([
            ['name' => 'Home Loan Finder', 'code' => 'S101', 'website' => 'homeloanfinder.example'],
            ['name' => 'Refinance Today', 'code' => 'S102', 'website' => 'refinancetoday.example'],
            ['name' => 'Partner Network', 'code' => 'P201', 'website' => 'partnernetwork.example'],
        ])->map(fn (array $source): LeadSource => LeadSource::factory()->withToken(self::DEMO_TOKEN_PREFIX.$source['code'])->create($source));

        foreach ($sources as $source) {
            Lead::factory()
                ->count(12)
                ->recycle($source)
                ->receivedWithinDays(4)
                ->create();

            foreach ($processors as $processor) {
                Lead::factory()
                    ->count(4)
                    ->recycle($source)
                    ->assignedTo($processor)
                    ->has(LeadAction::factory()->count(2)->state(['user_id' => $processor->id]), 'actions')
                    ->receivedWithinDays(4)
                    ->create();

                Lead::factory()
                    ->count(2)
                    ->recycle($source)
                    ->assignedTo($processor)
                    ->callbackWithinDays(-1, 5)
                    ->receivedWithinDays(4)
                    ->create();
            }
        }

        Lead::query()->with(['source', 'actions'])->each(function (Lead $lead): void {
            $lead->actions->each(fn (LeadAction $action) => $action->update([
                'created_at' => fake()->dateTimeBetween($lead->created_at, 'now'),
            ]));

            LeadAction::factory()->received($lead)->create();

            if ($lead->follow_up_at !== null) {
                LeadAction::factory()->scheduled($lead)->create();
            }
        });
    }
}
