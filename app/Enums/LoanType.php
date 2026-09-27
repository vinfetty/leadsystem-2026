<?php

namespace App\Enums;

enum LoanType: string
{
    case Purchase = 'purchase';
    case Refinance = 'refinance';
    case HomeEquity = 'home_equity';
    case DebtConsolidation = 'debt_consolidation';

    public function label(): string
    {
        return match ($this) {
            self::Purchase => 'Purchase',
            self::Refinance => 'Refinance',
            self::HomeEquity => 'Home equity',
            self::DebtConsolidation => 'Debt consolidation',
        };
    }
}
