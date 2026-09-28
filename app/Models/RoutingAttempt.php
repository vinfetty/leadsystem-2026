<?php

namespace App\Models;

use Database\Factories\RoutingAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One pass of a verified lead through the buyers.
 *
 * It records who got the lead and at what price, or that nobody could
 * take it, together with what was decided about every buyer. The
 * decisions are stored as they were worded at the time, because a
 * buyer's rules and caps change afterwards.
 *
 * A unique index on lead and buyer means a buyer can never be sent the
 * same lead twice.
 */
#[Fillable(['lead_id', 'user_id', 'buyer_id', 'price_cents', 'evaluations'])]
class RoutingAttempt extends Model
{
    /** @use HasFactory<RoutingAttemptFactory> */
    use HasFactory;

    public const CHOSEN = 'chosen';

    public const OUTRANKED = 'outranked';

    public const SKIPPED = 'skipped';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['evaluations' => 'array'];
    }

    /**
     * @return BelongsTo<Lead, $this>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * @return BelongsTo<Buyer, $this>
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    public function wasRouted(): bool
    {
        return $this->buyer_id !== null;
    }

    public function price(): ?string
    {
        return $this->price_cents === null ? null : '$'.number_format($this->price_cents / 100, 2);
    }
}
