<?php

namespace App\Models;

use App\Enums\CreditRating;
use App\Enums\LoanType;
use Carbon\CarbonImmutable;
use Database\Factories\BuyerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A lender or aggregator that buys leads, with the rules a lead must meet.
 *
 * In 2005 a buyer with its own requirements got its own copy of the
 * pages. Here a buyer is a row, and its requirements are data.
 */
#[Fillable([
    'name', 'tier', 'price_cents', 'daily_cap', 'active',
    'states', 'zip_prefixes', 'loan_types',
    'min_loan_amount', 'max_loan_amount', 'min_credit_rating',
])]
class Buyer extends Model
{
    /** @use HasFactory<BuyerFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'states' => 'array',
            'zip_prefixes' => 'array',
            'loan_types' => 'array',
            'min_credit_rating' => CreditRating::class,
        ];
    }

    /**
     * Every lead this buyer has been sent.
     *
     * @return HasMany<RoutingAttempt, $this>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(RoutingAttempt::class);
    }

    /**
     * The order buyers are offered a lead: best tier first, and within a
     * tier whoever pays most.
     *
     * @param  Builder<self>  $query
     */
    public function scopeInRoutingOrder(Builder $query): void
    {
        $query->orderBy('tier')->orderByDesc('price_cents')->orderBy('id');
    }

    /**
     * Counted from midnight in the business's time zone.
     */
    public function deliveredToday(): int
    {
        $midnight = CarbonImmutable::now(config('leads.business_timezone'))->startOfDay()->utc();

        return $this->deliveries()->where('created_at', '>=', $midnight)->count();
    }

    public function price(): string
    {
        return '$'.number_format($this->price_cents / 100, 2);
    }

    /**
     * The buyer's requirements in words, one per rule it has.
     *
     * @return array<int, string>
     */
    public function ruleSummary(): array
    {
        $loanTypes = array_map(
            fn (string $type): string => LoanType::from($type)->label(),
            $this->loan_types ?? [],
        );

        return array_values(array_filter([
            empty($this->states) ? null : implode(', ', $this->states),
            empty($this->zip_prefixes) ? null : 'ZIP '.implode(', ', $this->zip_prefixes),
            $loanTypes === [] ? null : implode(', ', $loanTypes),
            $this->loanRange(),
            $this->min_credit_rating === null ? null : $this->min_credit_rating->label().' credit or better',
        ]));
    }

    private function loanRange(): ?string
    {
        $min = $this->min_loan_amount === null ? null : '$'.number_format($this->min_loan_amount);
        $max = $this->max_loan_amount === null ? null : '$'.number_format($this->max_loan_amount);

        return match (true) {
            $min !== null && $max !== null => "{$min} to {$max}",
            $min !== null => "{$min} and up",
            $max !== null => "Up to {$max}",
            default => null,
        };
    }
}
