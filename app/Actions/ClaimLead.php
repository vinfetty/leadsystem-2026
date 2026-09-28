<?php

namespace App\Actions;

use App\Enums\LeadActionType;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Let a processor take a lead from the unassigned pool.
 */
class ClaimLead
{
    /**
     * The lead is taken by a single conditional UPDATE, so when two processors
     * press the button at the same moment the database gives it to one of
     * them and this returns false for the other.
     */
    public function handle(Lead $lead, User $processor): bool
    {
        return DB::transaction(function () use ($lead, $processor): bool {
            $claimed = Lead::query()
                ->whereKey($lead->id)
                ->whereNull('assigned_to')
                ->open()
                ->update(['assigned_to' => $processor->id]);

            if ($claimed === 0) {
                return false;
            }

            $lead->refresh();

            if ($lead->status === LeadStatus::New) {
                $lead->update(['status' => LeadStatus::Assigned]);
            }

            $lead->actions()->create([
                'user_id' => $processor->id,
                'type' => LeadActionType::Assigned,
                'note' => 'Taken by '.$processor->name,
            ]);

            return true;
        });
    }
}
