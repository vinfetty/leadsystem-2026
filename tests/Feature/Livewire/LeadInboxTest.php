<?php

namespace Tests\Feature\Livewire;

use App\Enums\LeadStatus;
use App\Livewire\LeadInbox;
use App\Models\Buyer;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\RoutingAttempt;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\LeadFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class LeadInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_sign_in(): void
    {
        $this->get(route('leads.index'))->assertRedirect(route('login'));
    }

    public function test_signed_in_processor_gets_the_inbox_page(): void
    {
        $processor = User::factory()->create();

        $response = $this->actingAs($processor)->get(route('leads.index'));

        $response->assertSeeLivewire(LeadInbox::class);
    }

    public function test_processor_sees_own_and_unassigned_leads_but_not_a_colleagues(): void
    {
        $processor = User::factory()->create();
        $this->leadNamed('Ownlead')->assignedTo($processor)->create();
        $this->leadNamed('Poollead')->create();
        $this->leadNamed('Colleaguelead')->assignedTo(User::factory()->create())->create();

        $inbox = Livewire::actingAs($processor)->test(LeadInbox::class);

        $inbox->assertSee(['Ownlead', 'Poollead'])->assertDontSee('Colleaguelead');
    }

    public function test_processor_cannot_reach_a_colleagues_lead_by_searching_for_it(): void
    {
        $processor = User::factory()->create();
        $this->leadNamed('Colleaguelead')->assignedTo(User::factory()->create())->create();

        $inbox = Livewire::actingAs($processor)->test(LeadInbox::class)->set('search', 'Colleaguelead');

        $inbox->assertDontSee('Colleaguelead')->assertSee('No leads match these filters.');
    }

    public function test_admin_sees_every_processors_leads(): void
    {
        $this->leadNamed('Firstprocessor')->assignedTo(User::factory()->create())->create();
        $this->leadNamed('Secondprocessor')->assignedTo(User::factory()->create())->create();

        $inbox = Livewire::actingAs(User::factory()->admin()->create())->test(LeadInbox::class);

        $inbox->assertSee(['Firstprocessor', 'Secondprocessor']);
    }

    public function test_lists_the_newest_lead_first(): void
    {
        $this->leadNamed('Olderlead')->create(['created_at' => now()->subDay()]);
        $this->leadNamed('Newerlead')->create(['created_at' => now()]);

        $inbox = Livewire::actingAs(User::factory()->create())->test(LeadInbox::class);

        $inbox->assertSeeInOrder(['Newerlead', 'Olderlead']);
    }

    public function test_hides_routed_and_dead_leads_until_every_status_is_chosen(): void
    {
        $this->leadNamed('Routedlead')->withStatus(LeadStatus::Routed)->create();
        $this->leadNamed('Deadlead')->withStatus(LeadStatus::Dead)->create();
        $this->leadNamed('Newlead')->create();

        $inbox = Livewire::actingAs(User::factory()->create())->test(LeadInbox::class);

        $inbox->assertSee('Newlead')->assertDontSee(['Routedlead', 'Deadlead']);
        $inbox->set('status', 'all')->assertSee(['Newlead', 'Routedlead', 'Deadlead']);
    }

    public function test_filters_by_status(): void
    {
        $this->leadNamed('Contactedlead')->withStatus(LeadStatus::Contacted)->create();
        $this->leadNamed('Newlead')->create();

        $inbox = Livewire::actingAs(User::factory()->create())->test(LeadInbox::class)->set('status', 'contacted');

        $inbox->assertSee('Contactedlead')->assertDontSee('Newlead');
    }

    public function test_filters_by_source(): void
    {
        $source = LeadSource::factory()->create();
        $this->leadNamed('Fromsource')->for($source, 'source')->create();
        $this->leadNamed('Fromelsewhere')->create();

        $inbox = Livewire::actingAs(User::factory()->create())->test(LeadInbox::class)->set('source', (string) $source->id);

        $inbox->assertSee('Fromsource')->assertDontSee('Fromelsewhere');
    }

    public function test_filters_by_state(): void
    {
        $this->leadNamed('Texan')->inState('TX')->create();
        $this->leadNamed('Ohioan')->inState('OH')->create();

        $inbox = Livewire::actingAs(User::factory()->create())->test(LeadInbox::class)->set('state', 'TX');

        $inbox->assertSee('Texan')->assertDontSee('Ohioan');
    }

    public function test_filters_to_the_processors_own_leads_or_the_unassigned_pool(): void
    {
        $processor = User::factory()->create();
        $this->leadNamed('Ownlead')->assignedTo($processor)->create();
        $this->leadNamed('Poollead')->create();

        $inbox = Livewire::actingAs($processor)->test(LeadInbox::class);

        $inbox->set('owner', 'mine')->assertSee('Ownlead')->assertDontSee('Poollead');
        $inbox->set('owner', 'unassigned')->assertSee('Poollead')->assertDontSee('Ownlead');
    }

    #[TestWith(['Dana Whitlock'])]
    #[TestWith(['whitlock dana'])]
    #[TestWith(['dana.w@example.com'])]
    #[TestWith(['(614) 555-0142'])]
    #[TestWith(['5550142'])]
    public function test_search_finds_a_lead_by_name_email_or_phone(string $term): void
    {
        Lead::factory()->create([
            'first_name' => 'Dana', 'last_name' => 'Whitlock',
            'email' => 'dana.w@example.com', 'phone' => '6145550142',
        ]);
        $this->leadNamed('Someoneelse')->create(['phone' => '2125550199']);

        $inbox = Livewire::actingAs(User::factory()->create())->test(LeadInbox::class)->set('search', $term);

        $inbox->assertSee('Whitlock')->assertDontSee('Someoneelse');
    }

    public function test_falls_back_to_the_default_view_for_unknown_filter_values_in_the_url(): void
    {
        $this->leadNamed('Openlead')->create();
        $this->leadNamed('Routedlead')->withStatus(LeadStatus::Routed)->create();

        $inbox = Livewire::actingAs(User::factory()->create())
            ->withQueryParams(['status' => "open' OR 1=1 --", 'state' => 'ZZ', 'source' => '1 OR 1=1', 'owner' => 'everyone'])
            ->test(LeadInbox::class);

        $inbox->assertSet('state', 'ZZ')->assertSee('Openlead')->assertDontSee('Routedlead');
    }

    public function test_escapes_lead_details_in_the_list(): void
    {
        $this->leadNamed('<script>alert("x")</script>')->create();

        $inbox = Livewire::actingAs(User::factory()->create())->test(LeadInbox::class);

        $inbox->assertSee('&lt;script&gt;', escape: false)->assertDontSee('<script>alert("x")</script>', escape: false);
    }

    public function test_counts_only_the_open_leads_the_processor_may_see(): void
    {
        $processor = User::factory()->create();
        Lead::factory()->count(2)->assignedTo($processor)->create();
        Lead::factory()->count(3)->create();
        Lead::factory()->assignedTo($processor)->withStatus(LeadStatus::Routed)->create();
        Lead::factory()->assignedTo(User::factory()->create())->create();

        $inbox = Livewire::actingAs($processor)->test(LeadInbox::class);

        $this->assertSame(['unassigned' => 3, 'mine' => 2, 'due' => 0, 'open' => 5], $inbox->instance()->summary);
    }

    public function test_call_backs_due_view_lists_only_leads_whose_time_has_arrived_soonest_first(): void
    {
        $this->travelTo('2026-03-10 15:00:00');
        $processor = User::factory()->create();
        $this->leadNamed('Duetoday')->assignedTo($processor)->callbackAt(CarbonImmutable::parse('2026-03-10 14:00'))->create();
        $this->leadNamed('Dueyesterday')->assignedTo($processor)->callbackAt(CarbonImmutable::parse('2026-03-09 14:00'))->create();
        $this->leadNamed('Duetomorrow')->assignedTo($processor)->callbackAt(CarbonImmutable::parse('2026-03-11 14:00'))->create();
        $this->leadNamed('Nocallback')->assignedTo($processor)->create();

        $inbox = Livewire::actingAs($processor)->test(LeadInbox::class)->call('show', 'due');

        $inbox->assertSet('due', true)
            ->assertSeeInOrder(['Dueyesterday', 'Duetoday'])
            ->assertDontSee(['Duetomorrow', 'Nocallback']);
        $this->assertSame(2, $inbox->instance()->summary['due']);
    }

    public function test_a_routed_lead_is_never_due(): void
    {
        $this->travelTo('2026-03-10 15:00:00');
        $processor = User::factory()->create();
        $this->leadNamed('Routedlead')->assignedTo($processor)
            ->callbackAt(CarbonImmutable::parse('2026-03-10 14:00'))
            ->withStatus(LeadStatus::Routed)
            ->create();

        $inbox = Livewire::actingAs($processor)->test(LeadInbox::class)->set('status', 'all')->set('due', true);

        $inbox->assertDontSee('Routedlead');
    }

    public function test_shows_which_buyer_a_routed_lead_went_to(): void
    {
        $buyer = Buyer::factory()->create(['name' => 'Alder National Bank']);
        $lead = Lead::factory()->withStatus(LeadStatus::Routed)->create();
        RoutingAttempt::factory()->for($lead)->for($buyer)->create();

        $inbox = Livewire::actingAs(User::factory()->admin()->create())->test(LeadInbox::class)->set('status', 'routed');

        $inbox->assertSee('to Alder National Bank');
    }

    public function test_only_an_admin_is_shown_the_way_to_the_buyers(): void
    {
        $asProcessor = $this->actingAs(User::factory()->create())->get(route('leads.index'));
        $asAdmin = $this->actingAs(User::factory()->admin()->create())->get(route('leads.index'));

        $asProcessor->assertDontSee(route('buyers.index'));
        $asAdmin->assertSee(route('buyers.index'));
    }

    public function test_links_each_lead_to_its_own_page(): void
    {
        $lead = Lead::factory()->create();

        $inbox = Livewire::actingAs(User::factory()->create())->test(LeadInbox::class);

        $inbox->assertSee(route('leads.show', $lead));
    }

    public function test_shows_a_notice_carried_over_from_the_lead_page(): void
    {
        session()->flash('notice', 'That lead is no longer available to you.');

        $inbox = Livewire::actingAs(User::factory()->create())->test(LeadInbox::class);

        $inbox->assertSee('That lead is no longer available to you.');
    }

    public function test_changing_a_filter_clears_the_selection(): void
    {
        $lead = Lead::factory()->create();

        $inbox = Livewire::actingAs(User::factory()->admin()->create())
            ->test(LeadInbox::class)
            ->set('selected', [$lead->id])
            ->set('status', 'all');

        $inbox->assertSet('selected', []);
    }

    public function test_select_all_chooses_the_page_and_a_second_press_clears_it(): void
    {
        $leads = Lead::factory()->count(2)->create();

        $inbox = Livewire::actingAs(User::factory()->admin()->create())->test(LeadInbox::class);

        $inbox->call('togglePage');
        $this->assertEqualsCanonicalizing($leads->modelKeys(), $inbox->get('selected'));
        $inbox->call('togglePage')->assertSet('selected', []);
    }

    public function test_processor_takes_an_unassigned_lead(): void
    {
        $processor = User::factory()->create();
        $lead = $this->leadNamed('Poollead')->create();

        $inbox = Livewire::actingAs($processor)->test(LeadInbox::class)->call('claim', $lead->id);

        $inbox->assertSee('Poollead is now yours.');
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id, 'assigned_to' => $processor->id, 'status' => LeadStatus::Assigned->value,
        ]);
        $this->assertDatabaseHas('lead_actions', [
            'lead_id' => $lead->id, 'user_id' => $processor->id, 'type' => 'assigned',
        ]);
    }

    public function test_processor_cannot_take_a_lead_that_a_colleague_already_has(): void
    {
        $colleague = User::factory()->create();
        $lead = Lead::factory()->assignedTo($colleague)->create();

        $inbox = Livewire::actingAs(User::factory()->create())->test(LeadInbox::class)->call('claim', $lead->id);

        $inbox->assertSee('That lead has already been taken.');
        $this->assertSame($colleague->id, $lead->refresh()->assigned_to);
        $this->assertDatabaseCount('lead_actions', 0);
    }

    public function test_admin_assigns_the_selected_leads_to_a_processor(): void
    {
        $admin = User::factory()->admin()->create();
        $processor = User::factory()->create(['name' => 'Robin Vale']);
        $leads = Lead::factory()->count(2)->create();
        $untouched = Lead::factory()->create();

        $inbox = Livewire::actingAs($admin)
            ->test(LeadInbox::class)
            ->set('selected', $leads->modelKeys())
            ->set('assignTo', $processor->id)
            ->call('assignSelected');

        $inbox->assertSee('2 leads assigned to Robin Vale.')->assertSet('selected', [])->assertSet('assignTo', null);
        $this->assertSame(2, $processor->leads()->where('status', LeadStatus::Assigned)->count());
        $this->assertNull($untouched->refresh()->assigned_to);
        $this->assertDatabaseHas('lead_actions', [
            'lead_id' => $leads->first()->id, 'user_id' => $admin->id, 'type' => 'assigned', 'note' => 'Assigned to Robin Vale',
        ]);
    }

    public function test_processor_is_forbidden_from_assigning_leads_to_someone(): void
    {
        $processor = User::factory()->create();
        $lead = Lead::factory()->create();

        $inbox = Livewire::actingAs($processor)
            ->test(LeadInbox::class)
            ->set('selected', [$lead->id])
            ->set('assignTo', User::factory()->create()->id)
            ->call('assignSelected');

        $inbox->assertForbidden();
        $this->assertNull($lead->refresh()->assigned_to);
    }

    public function test_assigning_requires_a_processor_to_be_chosen(): void
    {
        $lead = Lead::factory()->create();

        $inbox = Livewire::actingAs(User::factory()->admin()->create())
            ->test(LeadInbox::class)
            ->set('selected', [$lead->id])
            ->call('assignSelected');

        $inbox->assertHasErrors(['assignTo' => 'required'])->assertSee('Choose who to assign the leads to.');
        $this->assertNull($lead->refresh()->assigned_to);
    }

    public function test_assigning_requires_a_selection(): void
    {
        $processor = User::factory()->create();

        $inbox = Livewire::actingAs(User::factory()->admin()->create())
            ->test(LeadInbox::class)
            ->set('assignTo', $processor->id)
            ->call('assignSelected');

        $inbox->assertHasErrors(['selected' => 'required']);
        $this->assertDatabaseCount('lead_actions', 0);
    }

    private function leadNamed(string $lastName): LeadFactory
    {
        return Lead::factory()->state(['last_name' => $lastName]);
    }
}
