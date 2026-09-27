<?php

namespace App\Models;

use Database\Factories\LeadSourceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A site or partner that sends leads in.
 *
 * Replaces the `sitenum` column and the per-partner copies of every page
 * (`actionviewmort_ameriquest.php`, `actionviewmort_lmb.php`, …): a new
 * partner is now a row, not a new set of files.
 */
#[Fillable(['name', 'code', 'website', 'intake_token_hash', 'active'])]
#[Hidden(['intake_token_hash'])]
class LeadSource extends Model
{
    /** @use HasFactory<LeadSourceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    /**
     * @return HasMany<Lead, $this>
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public static function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    public static function findByToken(?string $plainToken): ?self
    {
        if ($plainToken === null || $plainToken === '') {
            return null;
        }

        return self::query()
            ->where('intake_token_hash', self::hashToken($plainToken))
            ->where('active', true)
            ->first();
    }
}
