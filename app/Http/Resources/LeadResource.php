<?php

namespace App\Http\Resources;

use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * What a sending site gets back: a receipt, not the stored record.
 *
 * @mixin Lead
 */
class LeadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'received_at' => $this->created_at->toIso8601String(),
            'duplicate' => ! $this->wasRecentlyCreated,
        ];
    }
}
