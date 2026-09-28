<?php

namespace Tests\Feature\Livewire;

use App\Enums\LeadStatus;
use App\Livewire\LeadDetail;
use App\Models\Lead;
use App\Models\LeadAction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LeadDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * "Now" is 11:00 am on Tuesday 10 March 2026 in New York.
     */
    private const NOW_UTC = '2026-03-10 15:00:00';

    public function test_guest_is_redirected_to_sign_in(): void
    {
        $lead = Lead::factory()->create();

        $this->get(route('leads.show', $lead))->assertRedirect(route('login'));
    }

    public function test_processor_opens_their_own_lead(): void
    {
        $processor = User::factory()->create();
        $lead = Lead::factory()->assignedTo($processor)->create([
            'first_name' => 'Dana', 'last_name' => 'Whitlock', 'phone' => '6145550142',
        ]);

        $response = $this->actingAs($processor)->get(route('leads.show', $lead));

        $response->assertSeeLivewire(LeadDetail::class)->assertSee(['Dana Whitlock', '(614) 555-0142']);
    }

    public function test_returns_404_for_a_colleagues_lead(): void
    {
        $lead = Lead::factory()->assignedTo(User::factory()->create())->create();

        $response = $this->actingAs(User::factory()->create())->get(route('leads.show', $lead));

        $response->assertNotFound();
    }

    public function test_returns_to_the_inbox_without_acting_once_the_lead_is_handed_to_a_colleague(): void
    {
        $processor = User::factory()->create();
        $lead = Lead::factory()->assignedTo($processor)->create();
        $page = Livewire::actingAs($processor)->test(LeadDetail::class, ['lead' => $lead]);
        $lead->update(['assigned_to' => User::factory()->create()->id]);

        $page->call('logAction');

        $page->assertRedirect(route('leads.index'));
        $this->assertSame('That lead is no longer available to you.', session('notice'));
        $this->assertSame(LeadStatus::Assigned, $lead->refresh()->status);
        $this->assertDatabaseCount('lead_actions', 0);
    }

    public function test_admin_opens_a_lead_assigned_to_anyone(): void
    {
        $lead = Lead::factory()->assignedTo(User::factory()->create())->create(['last_name' => 'Whitlock']);

        $page = Livewire::actingAs(User::factory()->admin()->create())->test(LeadDetail::class, ['lead' => $lead]);

        $page->assertSee('Whitlock');
    }

    public function test_shows_the_history_newest_first(): void
    {
        $processor = User::factory()->create();
        $lead = Lead::factory()->assignedTo($processor)->create();
        LeadAction::factory()->for($lead)->create(['note' => 'First call', 'created_at' => now()->subDay()]);
        LeadAction::factory()->for($lead)->create(['note' => 'Second call', 'created_at' => now()]);

        $page = Livewire::actingAs($processor)->test(LeadDetail::class, ['lead' => $lead]);

        $page->assertSeeInOrder(['Second call', 'First call']);
    }

    public function test_escapes_notes_in_the_history(): void
    {
        $processor = User::factory()->create();
        $lead = Lead::factory()->assignedTo($processor)->create();
        LeadAction::factory()->for($lead)->create(['note' => '<script>alert("x")</script>']);

        $page = Livewire::actingAs($processor)->test(LeadDetail::class, ['lead' => $lead]);

        $page->assertSee('&lt;script&gt;', escape: false)->assertDontSee('<script>alert("x")</script>', escape: false);
    }

    public function test_logging_a_call_marks_the_lead_contacted_and_adds_it_to_the_history(): void
    {
        $processor = User::factory()->create();
        $lead = Lead::factory()->assignedTo($processor)->create();

        $page = Livewire::actingAs($processor)
            ->test(LeadDetail::class, ['lead' => $lead])
            ->set('actionType', 'called')
            ->set('note', 'Wants a quote by Friday.')
            ->call('logAction');

        $page->assertHasNoErrors()->assertSet('note', '')->assertSee(['Saved to the history.', 'Wants a quote by Friday.']);
        $this->assertSame(LeadStatus::Contacted, $lead->refresh()->status);
        $this->assertDatabaseHas('lead_actions', [
            'lead_id' => $lead->id, 'user_id' => $processor->id, 'type' => 'called', 'note' => 'Wants a quote by Friday.',
        ]);
    }

    public function test_marking_a_lead_dead_requires_a_note(): void
    {
        $processor = User::factory()->create();
        $lead = Lead::factory()->assignedTo($processor)->create();

        $page = Livewire::actingAs($processor)
            ->test(LeadDetail::class, ['lead' => $lead])
            ->set('actionType', 'dead')
            ->call('logAction');

        $page->assertHasErrors(['note' => 'required'])->assertSee('Add a note to explain.');
        $this->assertSame(LeadStatus::Assigned, $lead->refresh()->status);
        $this->assertDatabaseCount('lead_actions', 0);
    }

    public function test_rejects_an_action_the_form_does_not_offer(): void
    {
        $processor = User::factory()->create();
        $lead = Lead::factory()->assignedTo($processor)->create();

        $page = Livewire::actingAs($processor)
            ->test(LeadDetail::class, ['lead' => $lead])
            ->set('actionType', 'assigned')
            ->set('note', 'Forged.')
            ->call('logAction');

        $page->assertHasErrors(['actionType' => 'in']);
        $this->assertDatabaseCount('lead_actions', 0);
    }

    public function test_reopening_a_dead_lead_returns_it_to_the_processor(): void
    {
        $processor = User::factory()->create();
        $lead = Lead::factory()->assignedTo($processor)->withStatus(LeadStatus::Dead)->create();

        $page = Livewire::actingAs($processor)
            ->test(LeadDetail::class, ['lead' => $lead])
            ->set('actionType', 'reopened')
            ->call('logAction');

        $page->assertHasNoErrors();
        $this->assertSame(LeadStatus::Assigned, $lead->refresh()->status);
    }

    public function test_processor_is_forbidden_from_working_a_lead_they_have_not_taken(): void
    {
        $lead = Lead::factory()->create();

        $page = Livewire::actingAs(User::factory()->create())
            ->test(LeadDetail::class, ['lead' => $lead])
            ->set('actionType', 'called')
            ->call('logAction');

        $page->assertForbidden();
        $this->assertSame(LeadStatus::New, $lead->refresh()->status);
        $this->assertDatabaseCount('lead_actions', 0);
    }

    public function test_scheduling_reads_the_time_as_the_leads_local_time(): void
    {
        $this->travelTo(self::NOW_UTC);
        $processor = User::factory()->create();
        $lead = Lead::factory()->inState('NY')->assignedTo($processor)->create();

        $page = Livewire::actingAs($processor)
            ->test(LeadDetail::class, ['lead' => $lead])
            ->set('callbackAt', '2026-03-11T14:30')
            ->call('scheduleCallback');

        $page->assertHasNoErrors()->assertSet('callbackAt', '')->assertSee('Call-back scheduled for Wed 11 Mar, 2:30 pm EDT.');
        $lead->refresh();
        $this->assertSame(LeadStatus::Scheduled, $lead->status);
        $this->assertSame('2026-03-11 18:30:00', $lead->follow_up_at->toDateTimeString());
    }

    public function test_scheduling_outside_calling_hours_shows_an_error_and_changes_nothing(): void
    {
        $this->travelTo(self::NOW_UTC);
        $processor = User::factory()->create();
        $lead = Lead::factory()->inState('NY')->assignedTo($processor)->create();

        $page = Livewire::actingAs($processor)
            ->test(LeadDetail::class, ['lead' => $lead])
            ->set('callbackAt', '2026-03-11T22:15')
            ->call('scheduleCallback');

        $page->assertSee('Choose a time from 8:00 am to before 9:00 pm where the lead lives.');
        $this->assertNull($lead->refresh()->follow_up_at);
        $this->assertDatabaseCount('lead_actions', 0);
    }

    public function test_scheduling_requires_a_time(): void
    {
        $processor = User::factory()->create();
        $lead = Lead::factory()->assignedTo($processor)->create();

        $page = Livewire::actingAs($processor)->test(LeadDetail::class, ['lead' => $lead])->call('scheduleCallback');

        $page->assertHasErrors(['callbackAt' => 'required'])->assertSee('Choose a date and time.');
    }

    public function test_scheduling_is_refused_on_a_closed_lead(): void
    {
        $this->travelTo(self::NOW_UTC);
        $processor = User::factory()->create();
        $lead = Lead::factory()->assignedTo($processor)->withStatus(LeadStatus::Closed)->create();

        $page = Livewire::actingAs($processor)
            ->test(LeadDetail::class, ['lead' => $lead])
            ->set('callbackAt', '2026-03-11T14:30')
            ->call('scheduleCallback');

        $page->assertHasErrors('callbackAt');
        $lead->refresh();
        $this->assertSame(LeadStatus::Closed, $lead->status);
        $this->assertNull($lead->follow_up_at);
    }

    public function test_processor_is_forbidden_from_scheduling_on_a_lead_they_have_not_taken(): void
    {
        $this->travelTo(self::NOW_UTC);
        $lead = Lead::factory()->create();

        $page = Livewire::actingAs(User::factory()->create())
            ->test(LeadDetail::class, ['lead' => $lead])
            ->set('callbackAt', '2026-03-11T14:30')
            ->call('scheduleCallback');

        $page->assertForbidden();
        $this->assertNull($lead->refresh()->follow_up_at);
    }

    public function test_shows_an_overdue_call_back_as_due(): void
    {
        $this->travelTo(self::NOW_UTC);
        $processor = User::factory()->create();
        $lead = Lead::factory()->inState('NY')->assignedTo($processor)
            ->callbackAt(CarbonImmutable::parse('2026-03-10 13:00', 'UTC'))
            ->create();

        $page = Livewire::actingAs($processor)->test(LeadDetail::class, ['lead' => $lead]);

        $page->assertSee(['Tue 10 Mar, 9:00 am EDT', 'due 2 hours ago']);
    }

    public function test_processor_takes_an_unassigned_lead_from_its_page(): void
    {
        $processor = User::factory()->create();
        $lead = Lead::factory()->create();

        $page = Livewire::actingAs($processor)->test(LeadDetail::class, ['lead' => $lead])->call('claim');

        $page->assertSee('This lead is now yours.');
        $this->assertSame($processor->id, $lead->refresh()->assigned_to);
    }

    public function test_processor_who_loses_the_lead_to_a_colleague_returns_to_the_inbox(): void
    {
        $lead = Lead::factory()->create();
        $page = Livewire::actingAs(User::factory()->create())->test(LeadDetail::class, ['lead' => $lead]);
        $colleague = User::factory()->create();
        $lead->update(['assigned_to' => $colleague->id]);

        $page->call('claim');

        $page->assertRedirect(route('leads.index'));
        $this->assertSame('That lead is no longer available to you.', session('notice'));
        $this->assertSame($colleague->id, $lead->refresh()->assigned_to);
    }

    public function test_taking_a_closed_lead_is_refused_and_returns_to_the_inbox(): void
    {
        $lead = Lead::factory()->withStatus(LeadStatus::Closed)->create();

        $page = Livewire::actingAs(User::factory()->create())->test(LeadDetail::class, ['lead' => $lead])->call('claim');

        $page->assertRedirect(route('leads.index'));
        $this->assertNull($lead->refresh()->assigned_to);
    }

    public function test_admin_assigns_the_lead_to_a_processor(): void
    {
        $admin = User::factory()->admin()->create();
        $processor = User::factory()->create(['name' => 'Robin Vale']);
        $lead = Lead::factory()->create();

        $page = Livewire::actingAs($admin)
            ->test(LeadDetail::class, ['lead' => $lead])
            ->set('assignTo', $processor->id)
            ->call('assign');

        $page->assertSee('Assigned to Robin Vale.')->assertSet('assignTo', null);
        $this->assertSame($processor->id, $lead->refresh()->assigned_to);
    }

    public function test_processor_is_forbidden_from_assigning_the_lead_to_someone(): void
    {
        $processor = User::factory()->create();
        $lead = Lead::factory()->assignedTo($processor)->create();

        $page = Livewire::actingAs($processor)
            ->test(LeadDetail::class, ['lead' => $lead])
            ->set('assignTo', User::factory()->create()->id)
            ->call('assign');

        $page->assertForbidden();
        $this->assertSame($processor->id, $lead->refresh()->assigned_to);
    }
}
