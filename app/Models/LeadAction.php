<?php

namespace App\Models;

use Database\Factories\LeadActionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One entry in a lead's history: a call, a note, an assignment.
 *
 * The original overwrote a single `action` and `notes` column on the lead,
 * so the history of who did what was lost on every save.
 */
#[Fillable(['lead_id', 'user_id', 'type', 'note'])]
class LeadAction extends Model
{
    /** @use HasFactory<LeadActionFactory> */
    use HasFactory;

    public const TYPES = ['received', 'assigned', 'called', 'left_message', 'scheduled', 'note', 'closed', 'dead'];

    /**
     * @return BelongsTo<Lead, $this>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
