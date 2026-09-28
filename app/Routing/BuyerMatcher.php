<?php

namespace App\Routing;

use App\Enums\SkipReason;
use App\Models\Buyer;
use App\Models\Lead;

/**
 * Decides whether one buyer can take one lead.
 *
 * The buyer's own rules are checked before its cap, so "cap reached" is
 * only ever said of a buyer that would otherwise have wanted the lead.
 */
final class BuyerMatcher
{
    public function reasonToSkip(Buyer $buyer, Lead $lead, int $deliveredToday, bool $alreadySent): ?SkipReason
    {
        return match (true) {
            ! $buyer->active => SkipReason::Paused,
            $alreadySent => SkipReason::AlreadySent,
            ! $this->servesState($buyer, $lead) => SkipReason::StateNotServed,
            ! $this->servesZip($buyer, $lead) => SkipReason::ZipNotServed,
            ! $this->buysLoanType($buyer, $lead) => SkipReason::LoanTypeNotBought,
            $buyer->min_loan_amount !== null && $lead->loan_amount < $buyer->min_loan_amount => SkipReason::LoanTooSmall,
            $buyer->max_loan_amount !== null && $lead->loan_amount > $buyer->max_loan_amount => SkipReason::LoanTooLarge,
            $buyer->min_credit_rating !== null && ! $lead->credit_rating->meets($buyer->min_credit_rating) => SkipReason::CreditTooLow,
            $buyer->daily_cap !== null && $deliveredToday >= $buyer->daily_cap => SkipReason::CapReached,
            default => null,
        };
    }

    /**
     * An empty list means the buyer has no rule of that kind and takes any.
     */
    private function servesState(Buyer $buyer, Lead $lead): bool
    {
        return empty($buyer->states) || in_array($lead->state, $buyer->states, true);
    }

    private function servesZip(Buyer $buyer, Lead $lead): bool
    {
        if (empty($buyer->zip_prefixes)) {
            return true;
        }

        return array_any($buyer->zip_prefixes, fn (string $prefix): bool => str_starts_with($lead->zip, $prefix));
    }

    private function buysLoanType(Buyer $buyer, Lead $lead): bool
    {
        return empty($buyer->loan_types) || in_array($lead->loan_type->value, $buyer->loan_types, true);
    }
}
