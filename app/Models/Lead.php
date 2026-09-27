<?php

namespace App\Models;

use App\Enums\CreditRating;
use App\Enums\LeadStatus;
use App\Enums\LoanType;
use Carbon\CarbonImmutable;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'lead_source_id', 'assigned_to', 'status',
    'first_name', 'last_name', 'email', 'phone', 'phone_secondary',
    'address', 'city', 'state', 'zip', 'timezone',
    'property_value', 'loan_amount', 'loan_type', 'credit_rating',
    'yearly_income', 'best_time_to_call',
    'consent_at', 'consent_ip', 'follow_up_at',
])]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
            'loan_type' => LoanType::class,
            'credit_rating' => CreditRating::class,
            'consent_at' => 'immutable_datetime',
            'follow_up_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<LeadSource, $this>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'lead_source_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function broker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return HasMany<LeadAction, $this>
     */
    public function actions(): HasMany
    {
        return $this->hasMany(LeadAction::class)->latest();
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', LeadStatus::open());
    }

    /**
     * Leads this user may see: every lead for an admin, and for a broker
     * their own leads plus the unassigned pool.
     *
     * @param  Builder<self>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $query->where(function (Builder $query) use ($user): void {
            $query->where('assigned_to', $user->id)->orWhereNull('assigned_to');
        });
    }

    /**
     * Match every word of the term against the name, email or phone number.
     *
     * @param  Builder<self>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        foreach (preg_split('/\s+/', trim($term), flags: PREG_SPLIT_NO_EMPTY) as $word) {
            $digits = preg_replace('/\D/', '', $word);

            $query->where(function (Builder $query) use ($word, $digits): void {
                $query->whereLike('first_name', "%{$word}%")
                    ->orWhereLike('last_name', "%{$word}%")
                    ->orWhereLike('email', "%{$word}%")
                    ->when(strlen($digits) >= 3, fn (Builder $query) => $query->orWhereLike('phone', "%{$digits}%"));
            });
        }
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function formattedPhone(): string
    {
        return preg_replace('/^(\d{3})(\d{3})(\d{4})$/', '($1) $2-$3', $this->phone);
    }

    public function isAssigned(): bool
    {
        return $this->assigned_to !== null;
    }

    public function loanToValue(): ?float
    {
        if ($this->property_value === 0) {
            return null;
        }

        return round($this->loan_amount / $this->property_value * 100, 1);
    }

    /**
     * The current time where the lead lives, for deciding whether to call.
     */
    public function localTime(?CarbonImmutable $now = null): CarbonImmutable
    {
        return ($now ?? CarbonImmutable::now())->setTimezone($this->timezone);
    }

    /**
     * Whether it is a reasonable hour to phone the lead (8am to 9pm their time).
     */
    public function isCallableNow(?CarbonImmutable $now = null): bool
    {
        $hour = $this->localTime($now)->hour;

        return $hour >= 8 && $hour < 21;
    }
}
