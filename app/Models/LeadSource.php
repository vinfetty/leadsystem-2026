<?php

namespace App\Models;

use Database\Factories\LeadSourceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A website that sends leads in.
 *
 * Replaces the `sitenum` number the 2005 system stamped on each lead.
 * Every source has its own intake token, so one can be switched off
 * without touching the others.
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
