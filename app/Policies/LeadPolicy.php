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
     * A broker sees their own leads and the unassigned pool, never a colleague's.
     */
    public function view(User $user, Lead $lead): bool
    {
        return $user->isAdmin() || ! $lead->isAssigned() || $lead->assigned_to === $user->id;
    }

    /**
     * Handing a lead to a named broker, or moving it between brokers.
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
