<?php

namespace App\Policies;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * A processor sees their own leads and the unassigned pool, never a colleague's.
     */
    public function view(User $user, Lead $lead): bool
    {
        return $user->isAdmin() || ! $lead->isAssigned() || $lead->assigned_to === $user->id;
    }

    /**
     * Logging calls and notes, scheduling a call-back, closing the lead.
     */
    public function update(User $user, Lead $lead): bool
    {
        return $user->isAdmin() || $lead->assigned_to === $user->id;
    }

    /**
     * Handing a lead to a named processor, or moving it between processors.
     */
    public function assign(User $user, Lead $lead): bool
    {
        return $user->isAdmin();
    }

    /**
     * Taking a lead from the unassigned pool for yourself.
     */
    public function claim(User $user, Lead $lead): bool
    {
        return ! $lead->isAssigned() && in_array($lead->status, LeadStatus::open(), true);
    }
}
