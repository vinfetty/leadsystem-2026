<?php

namespace App\Actions;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Hand a lead to a broker and record who decided it.
 */
class AssignLead
{
    public function handle(Lead $lead, User $broker, User $assignedBy): void
    {
        if ($lead->assigned_to === $broker->id) {
            return;
        }

        DB::transaction(function () use ($lead, $broker, $assignedBy): void {
            $lead->update([
                'assigned_to' => $broker->id,
                'status' => $lead->status === LeadStatus::New ? LeadStatus::Assigned : $lead->status,
            ]);

            $lead->actions()->create([
                'user_id' => $assignedBy->id,
                'type' => 'assigned',
                'note' => 'Assigned to '.$broker->name,
            ]);
        });
    }
}
