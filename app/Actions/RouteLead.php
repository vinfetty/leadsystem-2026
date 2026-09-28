<?php

namespace App\Actions;

use App\Enums\LeadActionType;
use App\Enums\LeadStatus;
use App\Models\Buyer;
use App\Models\Lead;
use App\Models\RoutingAttempt;
use App\Models\User;
use App\Routing\BuyerMatcher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Offer a verified lead to the buyers and record what was decided.
 *
 * The buyers are tried best tier first. The first one whose rules the
 * lead meets and whose cap has room takes it. If none can, the lead
 * goes to the rejected bucket with every buyer's reason attached.
 */
class RouteLead
{
    public function __construct(private BuyerMatcher $matcher) {}

    /**
     * One lead is routed at a time, so two leads verified in the same
     * moment cannot both take a buyer's last place under its cap.
     */
    public function handle(Lead $lead, User $verifiedBy): RoutingAttempt
    {
        return Cache::lock('route-lead', 10)->block(5, fn (): RoutingAttempt => DB::transaction(
            fn (): RoutingAttempt => $this->route($lead, $verifiedBy),
        ));
    }

    private function route(Lead $lead, User $verifiedBy): RoutingAttempt
    {
        $alreadySentTo = $lead->routingAttempts()->whereNotNull('buyer_id')->pluck('buyer_id')->all();
        $winner = null;
        $evaluations = [];

        foreach (Buyer::query()->inRoutingOrder()->get() as $buyer) {
            $reason = $this->matcher->reasonToSkip(
                $buyer, $lead, $buyer->deliveredToday(), in_array($buyer->id, $alreadySentTo, true),
            );

            $outcome = match (true) {
                $reason !== null => RoutingAttempt::SKIPPED,
                $winner !== null => RoutingAttempt::OUTRANKED,
                default => RoutingAttempt::CHOSEN,
            };

            if ($outcome === RoutingAttempt::CHOSEN) {
                $winner = $buyer;
            }

            $evaluations[] = [
                'buyer_id' => $buyer->id,
                'buyer' => $buyer->name,
                'tier' => $buyer->tier,
                'outcome' => $outcome,
                'reason' => $reason?->explain($buyer, $lead),
            ];
        }

        $attempt = $lead->routingAttempts()->create([
            'user_id' => $verifiedBy->id,
            'buyer_id' => $winner?->id,
            'price_cents' => $winner?->price_cents,
            'evaluations' => $evaluations,
        ]);

        $lead->update([
            'status' => $winner === null ? LeadStatus::Rejected : LeadStatus::Routed,
            'follow_up_at' => null,
        ]);

        $lead->actions()->create([
            'type' => $winner === null ? LeadActionType::Rejected : LeadActionType::Routed,
            'note' => $winner === null
                ? 'No buyer could take this lead'
                : "Routed to {$winner->name} for {$winner->price()}",
        ]);

        return $attempt;
    }
}
