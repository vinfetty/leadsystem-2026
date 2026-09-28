<?php

namespace App\Enums;

use App\Models\Buyer;
use App\Models\Lead;

/**
 * Why a buyer could not take a lead.
 *
 * In 2005 these rules lived in people's heads and in copies of pages.
 * Here each one is named, so the lead's page can say which rule sent
 * the lead elsewhere.
 */
enum SkipReason: string
{
    case Paused = 'paused';
    case AlreadySent = 'already_sent';
    case StateNotServed = 'state_not_served';
    case ZipNotServed = 'zip_not_served';
    case LoanTypeNotBought = 'loan_type_not_bought';
    case LoanTooSmall = 'loan_too_small';
    case LoanTooLarge = 'loan_too_large';
    case CreditTooLow = 'credit_too_low';
    case CapReached = 'cap_reached';

    /**
     * The reason in words, as it stood when the lead was routed.
     */
    public function explain(Buyer $buyer, Lead $lead): string
    {
        return match ($this) {
            self::Paused => 'Paused',
            self::AlreadySent => 'Already received this lead',
            self::StateNotServed => "Does not buy leads in {$lead->state}",
            self::ZipNotServed => "Does not buy leads in ZIP {$lead->zip}",
            self::LoanTypeNotBought => 'Does not buy '.strtolower($lead->loan_type->label()).' leads',
            self::LoanTooSmall => 'Loan is below its $'.number_format($buyer->min_loan_amount).' minimum',
            self::LoanTooLarge => 'Loan is above its $'.number_format($buyer->max_loan_amount).' maximum',
            self::CreditTooLow => 'Needs '.strtolower($buyer->min_credit_rating->label()).' credit or better',
            self::CapReached => 'Reached its cap of '.number_format($buyer->daily_cap).' for today',
        };
    }
}
