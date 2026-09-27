<?php

namespace App\Actions;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Let a broker take a lead from the unassigned pool.
 */
class ClaimLead
{
    /**
     * The lead is taken by a single conditional UPDATE, so when two brokers
     * press the button at the same moment the database gives it to one of
     * them and this returns false for the other.
     */
    public function handle(Lead $lead, User $broker): bool
    {
        return DB::transaction(function () use ($lead, $broker): bool {
            $claimed = Lead::query()
                ->whereKey($lead->id)
                ->whereNull('assigned_to')
                ->open()
                ->update(['assigned_to' => $broker->id]);

            if ($claimed === 0) {
                return false;
            }

            $lead->refresh();

            if ($lead->status === LeadStatus::New) {
                $lead->update(['status' => LeadStatus::Assigned]);
            }

            $lead->actions()->create([
                'user_id' => $broker->id,
                'type' => 'assigned',
                'note' => 'Taken by '.$broker->name,
            ]);

            return true;
        });
    }
}
