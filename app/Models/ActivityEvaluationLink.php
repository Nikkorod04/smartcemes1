<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A short-lived public evaluation link for one activity (Phase 2).
 *
 * The token is the credential, exactly like the needs-assessment share link:
 * it is stored so the office can revoke or extend it, and extending never
 * changes the token, so a QR already shown or printed keeps working.
 */
class ActivityEvaluationLink extends Model
{
    use HasFactory;

    public const MIN_DAYS = 1;

    public const MAX_DAYS = 7;

    public const DEFAULT_DAYS = 1;

    protected $fillable = [
        'activity_id',
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
     * "1 day" means the rest of today plus one calendar day, ending at
     * 11:59 PM — the session is over long before the link closes, and a
     * respondent who scans late can still finish.
     *
     * A link may be generated before the activity. In that case the clock
     * starts on the activity date, so a link minted a week early is still open
     * during the session. A past activity (backfilling) starts from now.
     */
    public static function expiryFor(int $days, ?Carbon $notBefore = null): Carbon
    {
        $days = max(self::MIN_DAYS, min(self::MAX_DAYS, $days));

        $anchor = $notBefore !== null && $notBefore->greaterThan(now())
            ? $notBefore->copy()
            : now();

        return $anchor->addDays($days)->endOfDay();
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function evaluations()
    {
        return $this->hasMany(ActivityEvaluation::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at')->where('expires_at', '>', now());
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        // Null is not "never expires": the column is nullable only to keep
        // MySQL from managing the timestamp (same rule as AssessmentLink).
        return $this->expires_at === null || $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired();
    }

    /** Route key: tokens are unique and non-sequential. */
    public function getRouteKeyName(): string
    {
        return 'token';
    }
}
