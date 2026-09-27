<?php

namespace Tests\Unit\Rules;

use App\Models\Lead;
use App\Rules\CallableTime;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class CallableTimeTest extends TestCase
{
    /**
     * "Now" is 11:00 am on Tuesday 10 March 2026 in New York and 8:00 am in Los Angeles.
     */
    private const NOW_UTC = '2026-03-10 15:00:00';

    #[TestWith(['2026-03-10T11:01'])]
    #[TestWith(['2026-03-11T08:00'])]
    #[TestWith(['2026-03-11T20:59'])]
    public function test_accepts_a_future_time_inside_the_leads_calling_hours(string $value): void
    {
        $this->travelTo(self::NOW_UTC);

        $this->assertSame([], $this->errorsFor($value, 'NY'));
    }

    #[TestWith(['2026-03-11T07:59'])]
    #[TestWith(['2026-03-11T21:00'])]
    #[TestWith(['2026-03-11T23:30'])]
    public function test_rejects_a_time_outside_the_leads_calling_hours(string $value): void
    {
        $this->travelTo(self::NOW_UTC);

        $this->assertSame(
            ['Choose a time from 8:00 am to before 9:00 pm where the lead lives.'],
            $this->errorsFor($value, 'NY'),
        );
    }

    #[TestWith(['2026-03-10T10:59'])]
    #[TestWith(['2026-03-09T14:00'])]
    public function test_rejects_a_time_that_has_already_passed(string $value): void
    {
        $this->travelTo(self::NOW_UTC);

        $this->assertSame(['Choose a time in the future.'], $this->errorsFor($value, 'NY'));
    }

    public function test_reads_the_time_in_the_leads_zone_not_the_servers(): void
    {
        $this->travelTo(self::NOW_UTC);

        $this->assertSame([], $this->errorsFor('2026-03-10T08:30', 'CA'));
        $this->assertSame(['Choose a time in the future.'], $this->errorsFor('2026-03-10T08:30', 'NY'));
    }

    #[TestWith(['next tuesday'])]
    #[TestWith(['2026-03-11 14:00'])]
    #[TestWith(['2026-02-31T14:00'])]
    public function test_rejects_a_value_that_is_not_a_date_and_time(string $value): void
    {
        $this->travelTo(self::NOW_UTC);

        $this->assertSame(['Choose a date and time.'], $this->errorsFor($value, 'NY'));
    }

    /**
     * @return array<int, string>
     */
    private function errorsFor(string $value, string $state): array
    {
        $lead = Lead::factory()->inState($state)->make(['lead_source_id' => null]);

        return Validator::make(['at' => $value], ['at' => [new CallableTime($lead)]])->errors()->all();
    }
}
