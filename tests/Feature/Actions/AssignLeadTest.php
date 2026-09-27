<?php

namespace Tests\Feature\Actions;

use App\Actions\AssignLead;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignLeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_gives_a_new_lead_to_the_broker_and_logs_who_assigned_it(): void
    {
        $admin = User::factory()->admin()->create();
        $broker = User::factory()->create(['name' => 'Robin Vale']);
        $lead = Lead::factory()->create();

        (new AssignLead)->handle($lead, $broker, $admin);

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id, 'assigned_to' => $broker->id, 'status' => LeadStatus::Assigned->value,
        ]);
        $this->assertDatabaseHas('lead_actions', [
            'lead_id' => $lead->id, 'user_id' => $admin->id, 'type' => 'assigned', 'note' => 'Assigned to Robin Vale',
        ]);
    }

    public function test_moving_a_lead_between_brokers_keeps_its_status(): void
    {
        $newBroker = User::factory()->create();
        $lead = Lead::factory()->assignedTo(User::factory()->create())->withStatus(LeadStatus::Contacted)->create();

        (new AssignLead)->handle($lead, $newBroker, User::factory()->admin()->create());

        $lead->refresh();
        $this->assertSame($newBroker->id, $lead->assigned_to);
        $this->assertSame(LeadStatus::Contacted, $lead->status);
    }

    public function test_does_not_log_anything_when_the_broker_already_has_the_lead(): void
    {
        $broker = User::factory()->create();
        $lead = Lead::factory()->assignedTo($broker)->create();

        (new AssignLead)->handle($lead, $broker, User::factory()->admin()->create());

        $this->assertDatabaseCount('lead_actions', 0);
    }
}
