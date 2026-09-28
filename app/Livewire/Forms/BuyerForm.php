<?php

namespace App\Livewire\Forms;

use App\Enums\CreditRating;
use App\Enums\LoanType;
use App\Models\Buyer;
use App\Support\StateTimeZone;
use Closure;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * A buyer and its rules, as typed. Lists are typed with commas between
 * the items, and a blank field means the buyer has no rule of that kind.
 */
class BuyerForm extends Form
{
    public ?int $buyerId = null;

    public string $name = '';

    public string $tier = '1';

    public string $price = '';

    public string $dailyCap = '';

    public bool $active = true;

    public string $states = '';

    public string $zipPrefixes = '';

    /** @var array<int, string> */
    public array $loanTypes = [];

    public string $minLoanAmount = '';

    public string $maxLoanAmount = '';

    public string $minCreditRating = '';

    public function fillFrom(Buyer $buyer): void
    {
        $this->buyerId = $buyer->id;
        $this->name = $buyer->name;
        $this->tier = (string) $buyer->tier;
        $this->price = number_format($buyer->price_cents / 100, 2, '.', '');
        $this->dailyCap = (string) $buyer->daily_cap;
        $this->active = $buyer->active;
        $this->states = implode(', ', $buyer->states ?? []);
        $this->zipPrefixes = implode(', ', $buyer->zip_prefixes ?? []);
        $this->loanTypes = $buyer->loan_types ?? [];
        $this->minLoanAmount = (string) $buyer->min_loan_amount;
        $this->maxLoanAmount = (string) $buyer->max_loan_amount;
        $this->minCreditRating = $buyer->min_credit_rating->value ?? '';
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'tier' => ['required', 'integer', 'between:1,9'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'between:0,10000'],
            'dailyCap' => ['nullable', 'integer', 'min:1'],
            'states' => [$this->eachItem(
                fn (string $state): bool => in_array($state, StateTimeZone::states(), true),
                ':item is not a state. Use two-letter codes such as OH, PA.',
            )],
            'zipPrefixes' => [$this->eachItem(
                fn (string $prefix): bool => preg_match('/^\d{1,5}$/', $prefix) === 1,
                ':item is not a ZIP code or the start of one.',
            )],
            'loanTypes' => ['array'],
            'loanTypes.*' => [Rule::enum(LoanType::class)],
            'minLoanAmount' => ['nullable', 'integer', 'min:0'],
            'maxLoanAmount' => ['nullable', 'integer', 'min:0', $this->notBelowMinimum()],
            'minCreditRating' => ['nullable', Rule::enum(CreditRating::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Give the buyer a name.',
            'tier.between' => 'The tier must be between 1 and 9.',
            'price.required' => 'Enter the price the buyer pays for a lead.',
            'price.decimal' => 'Enter the price in dollars and cents, such as 45 or 45.50.',
            'dailyCap.min' => 'A cap must be at least 1. Leave it blank for no cap.',
        ];
    }

    public function save(): Buyer
    {
        $this->validate();

        return Buyer::query()->updateOrCreate(['id' => $this->buyerId], [
            'name' => trim($this->name),
            'tier' => (int) $this->tier,
            'price_cents' => (int) round(((float) $this->price) * 100),
            'daily_cap' => $this->numberOrNull($this->dailyCap),
            'active' => $this->active,
            'states' => $this->listOrNull($this->states),
            'zip_prefixes' => $this->listOrNull($this->zipPrefixes),
            'loan_types' => $this->loanTypes === [] ? null : array_values($this->loanTypes),
            'min_loan_amount' => $this->numberOrNull($this->minLoanAmount),
            'max_loan_amount' => $this->numberOrNull($this->maxLoanAmount),
            'min_credit_rating' => $this->minCreditRating === '' ? null : $this->minCreditRating,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function items(string $list): array
    {
        $items = preg_split('/[\s,]+/', strtoupper($list), flags: PREG_SPLIT_NO_EMPTY);

        return array_values(array_unique($items));
    }

    /**
     * @return array<int, string>|null
     */
    private function listOrNull(string $list): ?array
    {
        return $this->items($list) ?: null;
    }

    private function numberOrNull(string $value): ?int
    {
        return trim($value) === '' ? null : (int) $value;
    }

    /**
     * @param  Closure(string): bool  $isValid
     */
    private function eachItem(Closure $isValid, string $message): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($isValid, $message): void {
            foreach ($this->items((string) $value) as $item) {
                if (! $isValid($item)) {
                    $fail(str_replace(':item', $item, $message));

                    return;
                }
            }
        };
    }

    private function notBelowMinimum(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (trim($this->minLoanAmount) !== '' && (int) $value < (int) $this->minLoanAmount) {
                $fail('The largest loan cannot be smaller than the smallest.');
            }
        };
    }
}
