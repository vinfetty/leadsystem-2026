<?php

namespace App\Actions;

use App\Enums\LeadActionType;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Hand a lead to a processor and record who decided it.
 */
class AssignLead
{
    public function handle(Lead $lead, User $processor, User $assignedBy): void
    {
        if ($lead->assigned_to === $processor->id) {
            return;
        }

        DB::transaction(function () use ($lead, $processor, $assignedBy): void {
            $lead->update([
                'assigned_to' => $processor->id,
                'status' => $lead->status === LeadStatus::New ? LeadStatus::Assigned : $lead->status,
            ]);

            $lead->actions()->create([
                'user_id' => $assignedBy->id,
                'type' => LeadActionType::Assigned,
                'note' => 'Assigned to '.$processor->name,
            ]);
        });
    }
}
