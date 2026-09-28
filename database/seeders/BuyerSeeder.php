<?php

namespace Database\Seeders;

use App\Enums\CreditRating;
use App\Enums\LoanType;
use App\Models\Buyer;
use Illuminate\Database\Seeder;

/**
 * Invented buyers, arranged so the demo shows every kind of rule at work:
 * a bank that buys by ZIP code, two regional lenders, broader second-tier
 * buyers, a paused buyer, and a low-priced buyer that catches what is left.
 */
class BuyerSeeder extends Seeder
{
    public function run(): void
    {
        Buyer::factory()
            ->tier(1)->payingCents(9500)->cappedAt(4)
            ->servingStates('OH')->servingZips('43', '44', '45')
            ->buying(LoanType::Purchase)
            ->needingCredit(CreditRating::Excellent)
            ->create(['name' => 'Greystone Community Bank']);

        Buyer::factory()
            ->tier(1)->payingCents(8500)->cappedAt(6)
            ->servingStates('OH', 'PA', 'NY', 'NJ', 'CT', 'MA', 'VA', 'NC', 'GA', 'FL')
            ->buying(LoanType::Refinance, LoanType::Purchase)
            ->lendingBetween(200000, null)
            ->needingCredit(CreditRating::Good)
            ->create(['name' => 'Alder National Bank']);

        Buyer::factory()
            ->tier(1)->payingCents(7000)->cappedAt(8)
            ->servingStates('CA', 'WA', 'OR', 'AZ', 'NV', 'CO', 'UT', 'TX')
            ->buying(LoanType::Refinance, LoanType::Purchase)
            ->lendingBetween(150000, 750000)
            ->needingCredit(CreditRating::Good)
            ->create(['name' => 'Birchline Mortgage']);

        Buyer::factory()
            ->tier(2)->payingCents(5000)->paused()
            ->create(['name' => 'Foxhall Capital']);

        Buyer::factory()
            ->tier(2)->payingCents(4500)->cappedAt(15)
            ->buying(LoanType::Refinance, LoanType::Purchase, LoanType::HomeEquity)
            ->lendingBetween(100000, null)
            ->needingCredit(CreditRating::Fair)
            ->create(['name' => 'Cedar Ridge Lending']);

        Buyer::factory()
            ->tier(2)->payingCents(3800)->cappedAt(12)
            ->buying(LoanType::HomeEquity, LoanType::DebtConsolidation)
            ->lendingBetween(null, 400000)
            ->needingCredit(CreditRating::Fair)
            ->create(['name' => 'Dovetail Home Equity']);

        Buyer::factory()
            ->tier(3)->payingCents(1500)
            ->buying(LoanType::Refinance, LoanType::Purchase, LoanType::HomeEquity)
            ->lendingBetween(75000, null)
            ->create(['name' => 'Elmstead Funding']);
    }
}
