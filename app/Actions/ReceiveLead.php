<?php

namespace App\Actions;

use App\Enums\LeadActionType;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Support\StateTimeZone;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Store an incoming lead, or return the one already on file.
 */
class ReceiveLead
{
    /**
     * A repeat submission inside this window is the same enquiry, not a new lead.
     */
    public const DUPLICATE_WINDOW_DAYS = 30;

    /**
     * @param  array<string, mixed>  $details  validated intake fields
     */
    public function handle(LeadSource $source, array $details, string $ipAddress): Lead
    {
        $existing = $this->recentLeadFor($source, $details['email']);

        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(function () use ($source, $details, $ipAddress): Lead {
            $lead = $source->leads()->create([
                ...collect($details)->except('consent')->all(),
                'phone' => $this->digits($details['phone']),
                'phone_secondary' => isset($details['phone_secondary']) ? $this->digits($details['phone_secondary']) : null,
                'status' => LeadStatus::New,
                'timezone' => StateTimeZone::for($details['state']),
                'consent_at' => CarbonImmutable::now(),
                'consent_ip' => $ipAddress,
            ]);

            $lead->actions()->create(['type' => LeadActionType::Received, 'note' => 'Received from '.$source->name]);

            return $lead;
        });
    }

    private function recentLeadFor(LeadSource $source, string $email): ?Lead
    {
        return $source->leads()
            ->where('email', $email)
            ->where('created_at', '>=', CarbonImmutable::now()->subDays(self::DUPLICATE_WINDOW_DAYS))
            ->latest()
            ->first();
    }

    private function digits(string $phone): string
    {
        return preg_replace('/\D/', '', $phone);
    }
}
