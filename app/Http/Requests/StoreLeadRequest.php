<?php

namespace App\Http\Requests;

use App\Enums\CreditRating;
use App\Enums\LoanType;
use App\Support\StateTimeZone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
{
    /**
     * The sending site is authenticated by the AuthenticateLeadSource middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:75'],
            'last_name' => ['required', 'string', 'max:75'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^\D*(\d\D*){10}$/'],
            'phone_secondary' => ['nullable', 'string', 'regex:/^\D*(\d\D*){10}$/'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:50'],
            'state' => ['required', 'string', Rule::in(StateTimeZone::states())],
            'zip' => ['required', 'string', 'regex:/^\d{5}(-\d{4})?$/'],
            'property_value' => ['required', 'integer', 'min:1'],
            'loan_amount' => ['required', 'integer', 'min:1'],
            'loan_type' => ['required', Rule::enum(LoanType::class)],
            'credit_rating' => ['required', Rule::enum(CreditRating::class)],
            'yearly_income' => ['nullable', 'integer', 'min:0'],
            'best_time_to_call' => ['nullable', 'string', 'max:50'],
            'consent' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'The phone number must have 10 digits.',
            'phone_secondary.regex' => 'The secondary phone number must have 10 digits.',
            'zip.regex' => 'The ZIP code must be 5 digits, or 5 plus 4.',
            'consent.accepted' => 'The lead must have agreed to be contacted.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'state' => strtoupper(trim((string) $this->input('state'))),
            'email' => strtolower(trim((string) $this->input('email'))),
        ]);
    }
}
