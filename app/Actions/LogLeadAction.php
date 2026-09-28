<?php

namespace App\Actions;

use App\Enums\LeadActionType;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\LeadAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Record what a person did with a lead and move the lead on accordingly.
 */
class LogLeadAction
{
    public function __construct(private RouteLead $routeLead) {}

    public function handle(Lead $lead, User $by, LeadActionType $type, ?string $note = null): LeadAction
    {
        if (! in_array($type, LeadActionType::loggableFor($lead->status), true)) {
            throw new InvalidArgumentException("A {$lead->status->value} lead cannot be marked {$type->value}.");
        }

        return DB::transaction(function () use ($lead, $by, $type, $note): LeadAction {
            $lead->update($this->changesFor($lead, $type));

            $action = $lead->actions()->create([
                'user_id' => $by->id,
                'type' => $type,
                'note' => $note === null || trim($note) === '' ? null : trim($note),
            ]);

            if ($type === LeadActionType::Verified) {
                $this->routeLead->handle($lead, $by);
            }

            return $action;
        });
    }

    /**
     * Reaching the lead or giving up on it settles any call-back that was
     * pending. A verified lead gets its status from where it is routed.
     *
     * @return array<string, mixed>
     */
    private function changesFor(Lead $lead, LeadActionType $type): array
    {
        return match ($type) {
            LeadActionType::Called => ['status' => LeadStatus::Contacted, 'follow_up_at' => null],
            LeadActionType::Dead => ['status' => LeadStatus::Dead, 'follow_up_at' => null],
            LeadActionType::Reopened => ['status' => $lead->isAssigned() ? LeadStatus::Assigned : LeadStatus::New],
            default => [],
        };
    }
}
