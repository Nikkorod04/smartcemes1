<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A public needs-assessment share link (after-defense revision, Item 1).
 *
 * Lifetime is deliberately short: the link is a field-visit credential, not a
 * permanent community subscription. It can be extended ("reopened") without
 * changing the token, so a printed QR keeps working.
 */
class AssessmentLink extends Model
{
    use HasFactory;

    public const MIN_DAYS = 1;

    public const MAX_DAYS = 5;

    public const DEFAULT_DAYS = 5;

    protected $fillable = [
        'community_id',
        'quarter',
        'year',
        'token',
        'expires_at',
        'created_by',
        'revoked_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /** URL-safe, unguessable, and short enough to survive being typed by hand. */
    public static function newToken(): string
    {
        return Str::random(32);
    }

    /**
     * "3 days" means three calendar days, ending at 11:59 PM — not a stopwatch
     * that starts when the button was clicked.
     */
    public static function expiryFor(int $days): Carbon
    {
        $days = max(self::MIN_DAYS, min(self::MAX_DAYS, $days));

        return now()->addDays($days)->endOfDay();
    }

    public function community()
    {
        return $this->belongsTo(Community::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired();
    }

    public function periodLabel(): string
    {
        return 'Q'.$this->quarter.' '.$this->year;
    }

    /** Route key: tokens are already unique and non-sequential. */
    public function getRouteKeyName(): string
    {
        return 'token';
    }
}
