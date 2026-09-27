<?php

namespace Tests\Feature\Actions;

use App\Actions\ScheduleCallback;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleCallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_stores_the_moment_in_utc_and_marks_the_lead_scheduled(): void
    {
        $broker = User::factory()->create();
        $lead = Lead::factory()->inState('NY')->assignedTo($broker)->create();

        (new ScheduleCallback)->handle($lead, $broker, CarbonImmutable::parse('2026-03-11 14:30', 'America/New_York'));

        $lead->refresh();
        $this->assertSame(LeadStatus::Scheduled, $lead->status);
        $this->assertSame('2026-03-11 18:30:00', $lead->follow_up_at->toDateTimeString());
    }

    public function test_logs_the_call_back_in_the_leads_own_time(): void
    {
        $broker = User::factory()->create();
        $lead = Lead::factory()->inState('CA')->assignedTo($broker)->create();

        (new ScheduleCallback)->handle($lead, $broker, CarbonImmutable::parse('2026-03-11 18:30', 'UTC'));

        $this->assertDatabaseHas('lead_actions', [
            'lead_id' => $lead->id,
            'user_id' => $broker->id,
            'type' => 'scheduled',
            'note' => 'Call back Wed 11 Mar 2026, 11:30 am PDT',
        ]);
    }

    public function test_rescheduling_replaces_the_earlier_time(): void
    {
        $broker = User::factory()->create();
        $lead = Lead::factory()->assignedTo($broker)->callbackAt(CarbonImmutable::parse('2026-03-11 18:30'))->create();

        (new ScheduleCallback)->handle($lead, $broker, CarbonImmutable::parse('2026-03-12 16:00', 'UTC'));

        $this->assertSame('2026-03-12 16:00:00', $lead->refresh()->follow_up_at->toDateTimeString());
    }
}
