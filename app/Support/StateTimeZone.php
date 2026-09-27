<?php

namespace App\Support;

/**
 * Resolve the time zone a lead should be called in from their state.
 *
 * The original system kept a 300-row `area_codes` table mapping phone
 * prefixes to an hour offset from the server, then added that offset by
 * hand on every page. Mobile numbers broke that assumption years ago.
 * Storing an IANA zone name on the lead lets Carbon handle daylight
 * saving and lets the page show "their local time" without arithmetic.
 *
 * States that span two zones get the zone most of their population lives in.
 */
final class StateTimeZone
{
    public const DEFAULT = 'America/New_York';

    /** @var array<string, string> */
    private const ZONES = [
        'AL' => 'America/Chicago',
        'AK' => 'America/Anchorage',
        'AZ' => 'America/Phoenix',
        'AR' => 'America/Chicago',
        'CA' => 'America/Los_Angeles',
        'CO' => 'America/Denver',
        'CT' => 'America/New_York',
        'DE' => 'America/New_York',
        'DC' => 'America/New_York',
        'FL' => 'America/New_York',
        'GA' => 'America/New_York',
        'HI' => 'Pacific/Honolulu',
        'ID' => 'America/Boise',
        'IL' => 'America/Chicago',
        'IN' => 'America/Indiana/Indianapolis',
        'IA' => 'America/Chicago',
        'KS' => 'America/Chicago',
        'KY' => 'America/New_York',
        'LA' => 'America/Chicago',
        'ME' => 'America/New_York',
        'MD' => 'America/New_York',
        'MA' => 'America/New_York',
        'MI' => 'America/Detroit',
        'MN' => 'America/Chicago',
        'MS' => 'America/Chicago',
        'MO' => 'America/Chicago',
        'MT' => 'America/Denver',
        'NE' => 'America/Chicago',
        'NV' => 'America/Los_Angeles',
        'NH' => 'America/New_York',
        'NJ' => 'America/New_York',
        'NM' => 'America/Denver',
        'NY' => 'America/New_York',
        'NC' => 'America/New_York',
        'ND' => 'America/Chicago',
        'OH' => 'America/New_York',
        'OK' => 'America/Chicago',
        'OR' => 'America/Los_Angeles',
        'PA' => 'America/New_York',
        'RI' => 'America/New_York',
        'SC' => 'America/New_York',
        'SD' => 'America/Chicago',
        'TN' => 'America/Chicago',
        'TX' => 'America/Chicago',
        'UT' => 'America/Denver',
        'VT' => 'America/New_York',
        'VA' => 'America/New_York',
        'WA' => 'America/Los_Angeles',
        'WV' => 'America/New_York',
        'WI' => 'America/Chicago',
        'WY' => 'America/Denver',
    ];

    public static function for(string $state): string
    {
        return self::ZONES[strtoupper(trim($state))] ?? self::DEFAULT;
    }

    /**
     * @return array<int, string>
     */
    public static function states(): array
    {
        return array_keys(self::ZONES);
    }
}
