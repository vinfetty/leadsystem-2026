<?php

namespace App\Enums;

/**
 * One kind of entry in a lead's history.
 */
enum LeadActionType: string
{
    case Received = 'received';
    case Assigned = 'assigned';
    case Called = 'called';
    case LeftMessage = 'left_message';
    case Scheduled = 'scheduled';
    case Note = 'note';
    case Closed = 'closed';
    case Dead = 'dead';
    case Reopened = 'reopened';

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Lead received',
            self::Assigned => 'Assigned',
            self::Called => 'Spoke with the lead',
            self::LeftMessage => 'Left a message',
            self::Scheduled => 'Call-back scheduled',
            self::Note => 'Note',
            self::Closed => 'Closed',
            self::Dead => 'Marked dead',
            self::Reopened => 'Reopened',
        };
    }

    /**
     * How the choice reads in the form, before it has happened.
     */
    public function prompt(): string
    {
        return match ($this) {
            self::Note => 'Add a note',
            self::Closed => 'Close the lead',
            self::Dead => 'Mark the lead dead',
            self::Reopened => 'Reopen the lead',
            default => $this->label(),
        };
    }

    /**
     * What a person may record by hand on a lead in the given status. The
     * other types are written by the system as a side effect of an action.
     *
     * @return array<int, self>
     */
    public static function loggableFor(LeadStatus $status): array
    {
        return in_array($status, LeadStatus::open(), true)
            ? [self::Called, self::LeftMessage, self::Note, self::Closed, self::Dead]
            : [self::Note, self::Reopened];
    }

    /**
     * A note and a dead lead both need an explanation to be worth recording.
     */
    public function requiresNote(): bool
    {
        return in_array($this, [self::Note, self::Dead], true);
    }
}
