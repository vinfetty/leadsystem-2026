<?php

namespace Tests\Feature\Actions;

use App\Actions\LogLeadAction;
use App\Enums\LeadActionType;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class LogLeadActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_records_the_action_with_who_did_it_and_the_trimmed_note(): void
    {
        $processor = User::factory()->create();
        $lead = Lead::factory()->assignedTo($processor)->create();

        (new LogLeadAction)->handle($lead, $processor, LeadActionType::LeftMessage, "  Voicemail, will try again.\n");

        $this->assertDatabaseHas('lead_actions', [
            'lead_id' => $lead->id,
            'user_id' => $processor->id,
            'type' => 'left_message',
            'note' => 'Voicemail, will try again.',
        ]);
    }

    public function test_stores_no_note_when_the_note_is_blank(): void
    {
        $processor = User::factory()->create();
        $lead = Lead::factory()->assignedTo($processor)->create();

        $action = (new LogLeadAction)->handle($lead, $processor, LeadActionType::Called, '   ');

        $this->assertNull($action->refresh()->note);
    }

    #[TestWith([LeadActionType::Called, LeadStatus::Contacted])]
    #[TestWith([LeadActionType::Closed, LeadStatus::Closed])]
    #[TestWith([LeadActionType::Dead, LeadStatus::Dead])]
    public function test_moves_the_lead_on_and_settles_its_pending_call_back(LeadActionType $type, LeadStatus $expected): void
    {
        $processor = User::factory()->create();
        $lead = Lead::factory()->assignedTo($processor)->callbackAt(CarbonImmutable::parse('2026-03-11 18:30'))->create();

        (new LogLeadAction)->handle($lead, $processor, $type, 'Done.');

        $lead->refresh();
        $this->assertSame($expected, $lead->status);
        $this->assertNull($lead->follow_up_at);
    }

    #[TestWith([LeadActionType::LeftMessage])]
    #[TestWith([LeadActionType::Note])]
    public function test_leaves_the_status_and_call_back_alone_for_a_message_or_a_note(LeadActionType $type): void
    {
        $processor = User::factory()->create();
        $lead = Lead::factory()->assignedTo($processor)->callbackAt(CarbonImmutable::parse('2026-03-11 18:30'))->create();

        (new LogLeadAction)->handle($lead, $processor, $type, 'Tried the mobile.');

        $lead->refresh();
        $this->assertSame(LeadStatus::Scheduled, $lead->status);
        $this->assertSame('2026-03-11 18:30:00', $lead->follow_up_at->toDateTimeString());
    }

    public function test_reopening_returns_an_assigned_lead_to_its_processor(): void
    {
        $processor = User::factory()->create();
        $lead = Lead::factory()->assignedTo($processor)->withStatus(LeadStatus::Dead)->create();

        (new LogLeadAction)->handle($lead, $processor, LeadActionType::Reopened);

        $this->assertSame(LeadStatus::Assigned, $lead->refresh()->status);
    }

    public function test_reopening_returns_an_unassigned_lead_to_the_pool_as_new(): void
    {
        $lead = Lead::factory()->withStatus(LeadStatus::Closed)->create();

        (new LogLeadAction)->handle($lead, User::factory()->admin()->create(), LeadActionType::Reopened);

        $this->assertSame(LeadStatus::New, $lead->refresh()->status);
    }

    #[TestWith([LeadStatus::Closed, LeadActionType::Called])]
    #[TestWith([LeadStatus::Dead, LeadActionType::Closed])]
    #[TestWith([LeadStatus::Assigned, LeadActionType::Reopened])]
    #[TestWith([LeadStatus::Assigned, LeadActionType::Received])]
    #[TestWith([LeadStatus::Assigned, LeadActionType::Scheduled])]
    public function test_refuses_an_action_that_does_not_fit_the_leads_status(LeadStatus $status, LeadActionType $type): void
    {
        $processor = User::factory()->create();
        $lead = Lead::factory()->assignedTo($processor)->withStatus($status)->create();

        try {
            (new LogLeadAction)->handle($lead, $processor, $type, 'Anything.');
            $this->fail('The action was accepted.');
        } catch (InvalidArgumentException) {
            $this->assertSame($status, $lead->refresh()->status);
            $this->assertDatabaseCount('lead_actions', 0);
        }
    }
}
