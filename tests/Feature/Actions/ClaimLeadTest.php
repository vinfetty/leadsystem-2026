<?php

namespace Tests\Feature\Actions;

use App\Actions\ClaimLead;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ClaimLeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_gives_an_unassigned_new_lead_to_the_processor_and_logs_it(): void
    {
        $processor = User::factory()->create(['name' => 'Robin Vale']);
        $lead = Lead::factory()->create();

        $claimed = (new ClaimLead)->handle($lead, $processor);

        $this->assertTrue($claimed);
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id, 'assigned_to' => $processor->id, 'status' => LeadStatus::Assigned->value,
        ]);
        $this->assertDatabaseHas('lead_actions', [
            'lead_id' => $lead->id, 'user_id' => $processor->id, 'type' => 'assigned', 'note' => 'Taken by Robin Vale',
        ]);
    }

    public function test_keeps_the_status_of_a_lead_that_was_already_being_worked(): void
    {
        $lead = Lead::factory()->withStatus(LeadStatus::Contacted)->create();

        (new ClaimLead)->handle($lead, User::factory()->create());

        $this->assertSame(LeadStatus::Contacted, $lead->refresh()->status);
    }

    public function test_second_processor_loses_when_both_loaded_the_lead_while_it_was_unassigned(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $lead = Lead::factory()->create();
        $copyLoadedBySecond = Lead::query()->findOrFail($lead->id);
        (new ClaimLead)->handle($lead, $first);

        $claimed = (new ClaimLead)->handle($copyLoadedBySecond, $second);

        $this->assertFalse($claimed);
        $this->assertSame($first->id, $lead->refresh()->assigned_to);
        $this->assertDatabaseMissing('lead_actions', ['user_id' => $second->id]);
    }

    #[TestWith([LeadStatus::Closed])]
    #[TestWith([LeadStatus::Dead])]
    public function test_refuses_a_lead_that_is_no_longer_open(LeadStatus $status): void
    {
        $lead = Lead::factory()->withStatus($status)->create();

        $claimed = (new ClaimLead)->handle($lead, User::factory()->create());

        $this->assertFalse($claimed);
        $this->assertNull($lead->refresh()->assigned_to);
        $this->assertDatabaseCount('lead_actions', 0);
    }
}
