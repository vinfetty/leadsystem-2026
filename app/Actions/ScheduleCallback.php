<?php

namespace App\Actions;

use App\Enums\LeadActionType;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Book a time to phone the lead back.
 *
 * The 2005 system stored the day and the time as two strings plus a
 * `timeplacer` integer to sort by, all in server time. Here the moment is
 * stored once, in UTC, and shown in the lead's own time zone.
 */
class ScheduleCallback
{
    public function handle(Lead $lead, User $by, CarbonImmutable $at): void
    {
        DB::transaction(function () use ($lead, $by, $at): void {
            $lead->update([
                'status' => LeadStatus::Scheduled,
                'follow_up_at' => $at->utc(),
            ]);

            $lead->actions()->create([
                'user_id' => $by->id,
                'type' => LeadActionType::Scheduled,
                'note' => 'Call back '.$at->setTimezone($lead->timezone)->format('D j M Y, g:i a T'),
            ]);
        });
    }
}
