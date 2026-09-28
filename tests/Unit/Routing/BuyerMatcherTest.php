<?php

namespace Tests\Unit\Routing;

use App\Enums\CreditRating;
use App\Enums\LoanType;
use App\Enums\SkipReason;
use App\Models\Buyer;
use App\Models\Lead;
use App\Routing\BuyerMatcher;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class BuyerMatcherTest extends TestCase
{
    public function test_a_buyer_with_no_rules_takes_any_lead(): void
    {
        $buyer = Buyer::factory()->make();

        $this->assertNull($this->reason($buyer, $this->lead()));
    }

    public function test_skips_a_paused_buyer(): void
    {
        $buyer = Buyer::factory()->paused()->make();

        $this->assertSame(SkipReason::Paused, $this->reason($buyer, $this->lead()));
    }

    public function test_skips_a_buyer_that_already_received_the_lead(): void
    {
        $buyer = Buyer::factory()->make();

        $this->assertSame(SkipReason::AlreadySent, $this->reason($buyer, $this->lead(), alreadySent: true));
    }

    #[TestWith(['OH', null])]
    #[TestWith(['PA', null])]
    #[TestWith(['TX', SkipReason::StateNotServed])]
    public function test_a_buyer_takes_leads_only_from_the_states_it_serves(string $state, ?SkipReason $expected): void
    {
        $buyer = Buyer::factory()->servingStates('OH', 'PA')->make();

        $this->assertSame($expected, $this->reason($buyer, $this->lead(['state' => $state])));
    }

    #[TestWith(['43215', null])]
    #[TestWith(['44101', null])]
    #[TestWith(['45999', SkipReason::ZipNotServed])]
    #[TestWith(['14321', SkipReason::ZipNotServed])]
    public function test_a_buyer_takes_leads_only_from_zip_codes_that_start_as_it_asks(string $zip, ?SkipReason $expected): void
    {
        $buyer = Buyer::factory()->servingZips('432', '44101')->make();

        $this->assertSame($expected, $this->reason($buyer, $this->lead(['zip' => $zip])));
    }

    #[TestWith([LoanType::Refinance, null])]
    #[TestWith([LoanType::DebtConsolidation, SkipReason::LoanTypeNotBought])]
    public function test_a_buyer_takes_only_the_loan_types_it_buys(LoanType $type, ?SkipReason $expected): void
    {
        $buyer = Buyer::factory()->buying(LoanType::Refinance, LoanType::Purchase)->make();

        $this->assertSame($expected, $this->reason($buyer, $this->lead(['loan_type' => $type])));
    }

    #[TestWith([149999, SkipReason::LoanTooSmall])]
    #[TestWith([150000, null])]
    #[TestWith([750000, null])]
    #[TestWith([750001, SkipReason::LoanTooLarge])]
    public function test_a_buyer_takes_loans_inside_its_range_ends_included(int $amount, ?SkipReason $expected): void
    {
        $buyer = Buyer::factory()->lendingBetween(150000, 750000)->make();

        $this->assertSame($expected, $this->reason($buyer, $this->lead(['loan_amount' => $amount])));
    }

    #[TestWith([CreditRating::Poor, SkipReason::CreditTooLow])]
    #[TestWith([CreditRating::Fair, SkipReason::CreditTooLow])]
    #[TestWith([CreditRating::Good, null])]
    #[TestWith([CreditRating::Excellent, null])]
    public function test_a_buyer_takes_leads_at_or_above_its_credit_floor(CreditRating $rating, ?SkipReason $expected): void
    {
        $buyer = Buyer::factory()->needingCredit(CreditRating::Good)->make();

        $this->assertSame($expected, $this->reason($buyer, $this->lead(['credit_rating' => $rating])));
    }

    #[TestWith([4, null])]
    #[TestWith([5, SkipReason::CapReached])]
    #[TestWith([6, SkipReason::CapReached])]
    public function test_a_buyer_stops_taking_leads_once_its_cap_is_reached(int $deliveredToday, ?SkipReason $expected): void
    {
        $buyer = Buyer::factory()->cappedAt(5)->make();

        $this->assertSame($expected, $this->reason($buyer, $this->lead(), $deliveredToday));
    }

    public function test_names_the_rule_the_lead_failed_even_when_the_cap_is_also_full(): void
    {
        $buyer = Buyer::factory()->servingStates('OH')->cappedAt(5)->make();

        $reason = $this->reason($buyer, $this->lead(['state' => 'TX']), deliveredToday: 5);

        $this->assertSame(SkipReason::StateNotServed, $reason);
    }

    public function test_explains_each_reason_with_the_buyers_own_figures(): void
    {
        $buyer = Buyer::factory()
            ->lendingBetween(150000, 750000)
            ->needingCredit(CreditRating::Good)
            ->cappedAt(15)
            ->make();
        $lead = $this->lead(['state' => 'TX', 'zip' => '75001', 'loan_type' => LoanType::DebtConsolidation]);

        $this->assertSame('Does not buy leads in TX', SkipReason::StateNotServed->explain($buyer, $lead));
        $this->assertSame('Does not buy leads in ZIP 75001', SkipReason::ZipNotServed->explain($buyer, $lead));
        $this->assertSame('Does not buy debt consolidation leads', SkipReason::LoanTypeNotBought->explain($buyer, $lead));
        $this->assertSame('Loan is below its $150,000 minimum', SkipReason::LoanTooSmall->explain($buyer, $lead));
        $this->assertSame('Loan is above its $750,000 maximum', SkipReason::LoanTooLarge->explain($buyer, $lead));
        $this->assertSame('Needs good credit or better', SkipReason::CreditTooLow->explain($buyer, $lead));
        $this->assertSame('Reached its cap of 15 for today', SkipReason::CapReached->explain($buyer, $lead));
    }

    private function reason(Buyer $buyer, Lead $lead, int $deliveredToday = 0, bool $alreadySent = false): ?SkipReason
    {
        return (new BuyerMatcher)->reasonToSkip($buyer, $lead, $deliveredToday, $alreadySent);
    }

    /**
     * A lead that every rule in these tests accepts, until a test changes one detail.
     *
     * @param  array<string, mixed>  $details
     */
    private function lead(array $details = []): Lead
    {
        return Lead::factory()->make([
            'lead_source_id' => null,
            'state' => 'OH',
            'zip' => '43215',
            'loan_type' => LoanType::Refinance,
            'loan_amount' => 300000,
            'credit_rating' => CreditRating::Good,
            ...$details,
        ]);
    }
}
