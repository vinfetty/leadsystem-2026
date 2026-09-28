<?php

namespace Tests\Feature\Actions;

use App\Actions\RouteLead;
use App\Enums\LeadStatus;
use App\Models\Buyer;
use App\Models\Lead;
use App\Models\RoutingAttempt;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteLeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_routes_the_lead_to_the_top_tier_buyer_at_that_buyers_price(): void
    {
        $processor = User::factory()->create();
        Buyer::factory()->tier(2)->payingCents(4500)->create();
        $top = Buyer::factory()->tier(1)->payingCents(8500)->create(['name' => 'Alder National Bank']);
        $lead = Lead::factory()->assignedTo($processor)->callbackAt(CarbonImmutable::parse('2026-03-11 18:30'))->create();

        $attempt = $this->route($lead, $processor);

        $this->assertSame($top->id, $attempt->buyer_id);
        $this->assertSame(8500, $attempt->price_cents);
        $lead->refresh();
        $this->assertSame(LeadStatus::Routed, $lead->status);
        $this->assertNull($lead->follow_up_at);
        $this->assertDatabaseHas('lead_actions', [
            'lead_id' => $lead->id, 'user_id' => null, 'type' => 'routed',
            'note' => 'Routed to Alder National Bank for $85.00',
        ]);
    }

    public function test_prefers_the_buyer_that_pays_more_within_a_tier(): void
    {
        Buyer::factory()->tier(1)->payingCents(7000)->create();
        $better = Buyer::factory()->tier(1)->payingCents(8500)->create();

        $attempt = $this->route(Lead::factory()->create());

        $this->assertSame($better->id, $attempt->buyer_id);
    }

    public function test_falls_to_the_next_tier_when_the_lead_fails_the_top_buyers_rules(): void
    {
        Buyer::factory()->tier(1)->servingStates('OH')->create();
        $second = Buyer::factory()->tier(2)->create();

        $attempt = $this->route(Lead::factory()->inState('TX')->create());

        $this->assertSame($second->id, $attempt->buyer_id);
    }

    public function test_falls_to_the_next_tier_when_the_top_buyers_cap_is_full(): void
    {
        $top = Buyer::factory()->tier(1)->cappedAt(2)->create();
        $second = Buyer::factory()->tier(2)->create();
        RoutingAttempt::factory()->for($top)->count(2)->create();

        $attempt = $this->route(Lead::factory()->create());

        $this->assertSame($second->id, $attempt->buyer_id);
    }

    public function test_a_cap_counts_only_leads_delivered_since_midnight_where_the_business_is(): void
    {
        config(['leads.business_timezone' => 'America/New_York']);
        $buyer = Buyer::factory()->cappedAt(1)->create();
        $this->travelTo(CarbonImmutable::parse('2026-03-10 23:30', 'America/New_York'));
        RoutingAttempt::factory()->for($buyer)->create();

        $this->travelTo(CarbonImmutable::parse('2026-03-11 00:30', 'America/New_York'));
        $attempt = $this->route(Lead::factory()->create());

        $this->assertSame($buyer->id, $attempt->buyer_id);
    }

    public function test_rejects_the_lead_when_no_buyer_can_take_it(): void
    {
        Buyer::factory()->servingStates('OH')->create();
        $lead = Lead::factory()->inState('TX')->create();

        $attempt = $this->route($lead);

        $this->assertFalse($attempt->wasRouted());
        $this->assertNull($attempt->price_cents);
        $this->assertSame(LeadStatus::Rejected, $lead->refresh()->status);
        $this->assertDatabaseHas('lead_actions', [
            'lead_id' => $lead->id, 'type' => 'rejected', 'note' => 'No buyer could take this lead',
        ]);
    }

    public function test_rejects_the_lead_when_there_are_no_buyers_at_all(): void
    {
        $lead = Lead::factory()->create();

        $attempt = $this->route($lead);

        $this->assertFalse($attempt->wasRouted());
        $this->assertSame([], $attempt->evaluations);
    }

    public function test_records_what_was_decided_about_every_buyer_in_the_order_they_were_offered(): void
    {
        $paused = Buyer::factory()->tier(1)->payingCents(9000)->paused()->create(['name' => 'Paused Bank']);
        $wrongState = Buyer::factory()->tier(1)->payingCents(8000)->servingStates('OH')->create(['name' => 'Ohio Bank']);
        $chosen = Buyer::factory()->tier(2)->payingCents(5000)->create(['name' => 'Second Lender']);
        $outranked = Buyer::factory()->tier(3)->payingCents(1500)->create(['name' => 'Third Lender']);

        $attempt = $this->route(Lead::factory()->inState('TX')->create());

        $this->assertSame([
            ['buyer_id' => $paused->id, 'buyer' => 'Paused Bank', 'tier' => 1, 'outcome' => 'skipped', 'reason' => 'Paused'],
            ['buyer_id' => $wrongState->id, 'buyer' => 'Ohio Bank', 'tier' => 1, 'outcome' => 'skipped', 'reason' => 'Does not buy leads in TX'],
            ['buyer_id' => $chosen->id, 'buyer' => 'Second Lender', 'tier' => 2, 'outcome' => 'chosen', 'reason' => null],
            ['buyer_id' => $outranked->id, 'buyer' => 'Third Lender', 'tier' => 3, 'outcome' => 'outranked', 'reason' => null],
        ], $attempt->evaluations);
    }

    public function test_never_sends_a_lead_to_a_buyer_that_already_received_it(): void
    {
        $first = Buyer::factory()->tier(1)->create();
        $second = Buyer::factory()->tier(2)->create();
        $lead = Lead::factory()->create();
        $this->route($lead);

        $attempt = $this->route($lead);

        $this->assertSame($second->id, $attempt->buyer_id);
        $this->assertSame('Already received this lead', $attempt->evaluations[0]['reason']);
        $this->assertSame(1, $first->deliveries()->count());
    }

    public function test_rejects_a_lead_that_every_buyer_has_already_received(): void
    {
        Buyer::factory()->create();
        $lead = Lead::factory()->create();
        $this->route($lead);

        $attempt = $this->route($lead);

        $this->assertFalse($attempt->wasRouted());
        $this->assertSame(LeadStatus::Rejected, $lead->refresh()->status);
    }

    private function route(Lead $lead, ?User $verifiedBy = null): RoutingAttempt
    {
        return app(RouteLead::class)->handle($lead, $verifiedBy ?? User::factory()->create());
    }
}
