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
    case Verified = 'verified';
    case Routed = 'routed';
    case Rejected = 'rejected';
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
            self::Verified => 'Verified by phone',
            self::Routed => 'Routed to a buyer',
            self::Rejected => 'Rejected',
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
            self::Verified => 'Verified, send to a buyer',
            self::Dead => 'Mark the lead dead',
            self::Reopened => 'Reopen the lead',
            default => $this->label(),
        };
    }

    /**
     * What a person may record by hand on a lead in the given status. The
     * other types are written by the system as a side effect of an action.
     *
     * A routed lead has been delivered, so it takes notes and nothing else.
     *
     * @return array<int, self>
     */
    public static function loggableFor(LeadStatus $status): array
    {
        return match (true) {
            in_array($status, LeadStatus::open(), true) => [self::Called, self::LeftMessage, self::Note, self::Verified, self::Dead],
            $status === LeadStatus::Routed => [self::Note],
            default => [self::Note, self::Reopened],
        };
    }

    /**
     * A note and a dead lead both need an explanation to be worth recording.
     */
    public function requiresNote(): bool
    {
        return in_array($this, [self::Note, self::Dead], true);
    }
}
