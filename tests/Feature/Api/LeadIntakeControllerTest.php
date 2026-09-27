<?php

namespace Tests\Feature\Api;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\LeadSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class LeadIntakeControllerTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'partner-site-token';

    public function test_valid_payload_creates_lead_and_returns_201(): void
    {
        $source = LeadSource::factory()->withToken(self::TOKEN)->create();

        $response = $this->withToken(self::TOKEN)->postJson(route('api.v1.leads.store'), $this->payload());

        $response->assertCreated()->assertJsonPath('data.duplicate', false);
        $this->assertDatabaseHas('leads', [
            'lead_source_id' => $source->id,
            'email' => 'pat.buyer@example.com',
            'status' => LeadStatus::New->value,
            'phone' => '6145550142',
        ]);
    }

    public function test_stores_the_time_zone_of_the_leads_state(): void
    {
        LeadSource::factory()->withToken(self::TOKEN)->create();

        $this->withToken(self::TOKEN)->postJson(route('api.v1.leads.store'), $this->payload(['state' => 'ca']));

        $this->assertSame('America/Los_Angeles', Lead::sole()->timezone);
    }

    public function test_records_when_and_from_where_consent_was_given(): void
    {
        LeadSource::factory()->withToken(self::TOKEN)->create();
        $this->freezeSecond();

        $this->withToken(self::TOKEN)
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->postJson(route('api.v1.leads.store'), $this->payload());

        $lead = Lead::sole();
        $this->assertTrue($lead->consent_at->equalTo(now()));
        $this->assertSame('203.0.113.9', $lead->consent_ip);
    }

    public function test_logs_a_received_action_on_the_new_lead(): void
    {
        LeadSource::factory()->withToken(self::TOKEN)->create();

        $this->withToken(self::TOKEN)->postJson(route('api.v1.leads.store'), $this->payload());

        $this->assertDatabaseHas('lead_actions', ['lead_id' => Lead::sole()->id, 'type' => 'received']);
    }

    public function test_returns_401_when_no_token_is_provided(): void
    {
        LeadSource::factory()->withToken(self::TOKEN)->create();

        $this->postJson(route('api.v1.leads.store'), $this->payload())->assertUnauthorized();

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_returns_401_when_the_token_is_unknown(): void
    {
        LeadSource::factory()->withToken(self::TOKEN)->create();

        $this->withToken('some-other-token')
            ->postJson(route('api.v1.leads.store'), $this->payload())
            ->assertUnauthorized();
    }

    public function test_returns_401_when_the_source_is_inactive(): void
    {
        LeadSource::factory()->withToken(self::TOKEN)->inactive()->create();

        $this->withToken(self::TOKEN)
            ->postJson(route('api.v1.leads.store'), $this->payload())
            ->assertUnauthorized();
    }

    public function test_repeat_submission_returns_200_and_does_not_create_a_second_lead(): void
    {
        LeadSource::factory()->withToken(self::TOKEN)->create();
        $this->withToken(self::TOKEN)->postJson(route('api.v1.leads.store'), $this->payload());

        $response = $this->withToken(self::TOKEN)
            ->postJson(route('api.v1.leads.store'), $this->payload(['email' => 'Pat.Buyer@Example.com']));

        $response->assertOk()->assertJsonPath('data.duplicate', true);
        $this->assertDatabaseCount('leads', 1);
    }

    public function test_same_email_creates_a_new_lead_after_the_duplicate_window(): void
    {
        LeadSource::factory()->withToken(self::TOKEN)->create();
        $this->withToken(self::TOKEN)->postJson(route('api.v1.leads.store'), $this->payload());

        $this->travel(31)->days();
        $response = $this->withToken(self::TOKEN)->postJson(route('api.v1.leads.store'), $this->payload());

        $response->assertCreated();
        $this->assertDatabaseCount('leads', 2);
    }

    public function test_same_email_from_another_source_creates_its_own_lead(): void
    {
        LeadSource::factory()->withToken(self::TOKEN)->create();
        LeadSource::factory()->withToken('second-site-token')->create();
        $this->withToken(self::TOKEN)->postJson(route('api.v1.leads.store'), $this->payload());

        $response = $this->withToken('second-site-token')->postJson(route('api.v1.leads.store'), $this->payload());

        $response->assertCreated();
        $this->assertDatabaseCount('leads', 2);
    }

    public function test_returns_422_for_an_empty_payload(): void
    {
        LeadSource::factory()->withToken(self::TOKEN)->create();

        $response = $this->withToken(self::TOKEN)->postJson(route('api.v1.leads.store'), []);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'first_name', 'last_name', 'email', 'phone', 'address', 'city', 'state', 'zip',
            'property_value', 'loan_amount', 'loan_type', 'credit_rating', 'consent',
        ]);
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_returns_422_when_the_lead_did_not_consent_to_contact(): void
    {
        LeadSource::factory()->withToken(self::TOKEN)->create();

        $response = $this->withToken(self::TOKEN)
            ->postJson(route('api.v1.leads.store'), $this->payload(['consent' => false]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['consent' => 'The lead must have agreed to be contacted.']);
        $this->assertDatabaseCount('leads', 0);
    }

    #[TestWith(['phone', '555-0142', 'The phone number must have 10 digits.'])]
    #[TestWith(['zip', '4321', 'The ZIP code must be 5 digits, or 5 plus 4.'])]
    #[TestWith(['state', 'ZZ', 'The selected state is invalid.'])]
    #[TestWith(['loan_type', 'payday', 'The selected loan type is invalid.'])]
    #[TestWith(['credit_rating', 'stellar', 'The selected credit rating is invalid.'])]
    #[TestWith(['loan_amount', 0, 'The loan amount field must be at least 1.'])]
    public function test_returns_422_for_an_invalid_field(string $field, string|int $value, string $message): void
    {
        LeadSource::factory()->withToken(self::TOKEN)->create();

        $response = $this->withToken(self::TOKEN)
            ->postJson(route('api.v1.leads.store'), $this->payload([$field => $value]));

        $response->assertUnprocessable()->assertJsonValidationErrors([$field => $message]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'first_name' => 'Pat',
            'last_name' => 'Buyer',
            'email' => 'pat.buyer@example.com',
            'phone' => '(614) 555-0142',
            'address' => '12 Elm Street',
            'city' => 'Columbus',
            'state' => 'OH',
            'zip' => '43215',
            'property_value' => 310000,
            'loan_amount' => 248000,
            'loan_type' => 'refinance',
            'credit_rating' => 'good',
            'yearly_income' => 88000,
            'best_time_to_call' => 'Evening',
            'consent' => true,
            ...$overrides,
        ];
    }
}
