<?php

namespace App\Rules;

use App\Models\Lead;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * A call-back time, typed as the lead's own local time, that is still
 * ahead of us and falls inside the hours the lead may be phoned.
 */
class CallableTime implements ValidationRule
{
    public const FORMAT = 'Y-m-d\TH:i';

    public function __construct(private Lead $lead) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $at = self::parse($value, $this->lead);

        if ($at === null) {
            $fail('Choose a date and time.');

            return;
        }

        if ($at->isPast()) {
            $fail('Choose a time in the future.');

            return;
        }

        if (! $this->lead->isWithinCallingHours($at)) {
            $fail('Choose a time from 8:00 am to before 9:00 pm where the lead lives.');
        }
    }

    /**
     * Read the form value as a moment in the lead's time zone.
     */
    public static function parse(mixed $value, Lead $lead): ?CarbonImmutable
    {
        if (! is_string($value)) {
            return null;
        }

        try {
            $at = CarbonImmutable::createFromFormat(self::FORMAT, $value, $lead->timezone)->startOfMinute();
        } catch (InvalidFormatException) {
            return null;
        }

        // PHP rolls 31 February over into March instead of refusing it.
        return $at->format(self::FORMAT) === $value ? $at : null;
    }
}
