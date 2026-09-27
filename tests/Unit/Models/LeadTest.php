<?php

namespace Tests\Unit\Models;

use App\Models\Lead;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class LeadTest extends TestCase
{
    #[TestWith(['2026-01-15 12:59:00', false])]
    #[TestWith(['2026-01-15 13:00:00', true])]
    #[TestWith(['2026-01-16 01:59:00', true])]
    #[TestWith(['2026-01-16 02:00:00', false])]
    public function test_is_callable_between_8am_and_9pm_in_the_leads_own_time_zone(string $utc, bool $callable): void
    {
        $lead = Lead::factory()->inState('NY')->make(['lead_source_id' => null]);

        $this->assertSame($callable, $lead->isCallableNow(CarbonImmutable::parse($utc, 'UTC')));
    }

    public function test_local_time_follows_daylight_saving(): void
    {
        $lead = Lead::factory()->inState('NY')->make(['lead_source_id' => null]);

        $this->assertSame(7, $lead->localTime(CarbonImmutable::parse('2026-01-15 12:00', 'UTC'))->hour);
        $this->assertSame(8, $lead->localTime(CarbonImmutable::parse('2026-07-15 12:00', 'UTC'))->hour);
    }

    public function test_loan_to_value_is_the_loan_as_a_percentage_of_the_property(): void
    {
        $lead = Lead::factory()->make(['lead_source_id' => null, 'property_value' => 300000, 'loan_amount' => 240000]);

        $this->assertSame(80.0, $lead->loanToValue());
    }
}
