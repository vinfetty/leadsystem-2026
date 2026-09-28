<?php

namespace App\Enums;

/**
 * Where a lead sits on its way to a buyer.
 *
 * The 2005 system tracked this as a bare integer column called
 * `actionlevel` (0, 20, 30 …) whose meaning lived in the heads of the
 * people using it. Naming the states makes the queries readable and
 * lets the type system reject anything else.
 */
enum LeadStatus: string
{
    case New = 'new';
    case Assigned = 'assigned';
    case Contacted = 'contacted';
    case Scheduled = 'scheduled';
    case Routed = 'routed';
    case Rejected = 'rejected';
    case Dead = 'dead';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Assigned => 'Assigned',
            self::Contacted => 'Contacted',
            self::Scheduled => 'Call scheduled',
            self::Routed => 'Routed',
            self::Rejected => 'Rejected',
            self::Dead => 'Dead',
        };
    }

    /**
     * Statuses a processor still needs to work.
     *
     * @return array<int, self>
     */
    public static function open(): array
    {
        return [self::New, self::Assigned, self::Contacted, self::Scheduled];
    }
}
