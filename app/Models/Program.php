<?php

namespace App\Models;

use App\Services\SequenceService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Program — the BROAD level (revision §4.2 / Phase R1).
 *
 * Revised hierarchy: College → Program → Project → Activity.
 * One row per CESO thrust (§3); six rows in practice.
 *
 * NAMING (R1 decision option 1, CONFIRMED in R2 step 4)
 * ----------------------------------------------------
 * Both the table AND the class are named for the broad level: `programs` /
 * `Program`. R2 explicitly kept this rather than renaming to
 * `extension_programs`, because R2's rename is already the highest-risk step
 * and adopting the name the legacy side was vacating would have inverted the
 * meaning of the word "program" mid-phase. See the R2a migration docblock.
 *
 * The legacy entity is now `ExtensionProject` / `extension_projects` (facts on
 * the ground), so the two are finally unambiguous:
 *
 *     College  -> colleges
 *     Program  -> programs            (BROAD, CESO thrust, no target)
 *     Project  -> extension_projects  (NARROW, carries activities + targets)
 *
 * Note: `Program` deliberately does NOT collide with `ProgramObjective` /
 * `ProgramNarrative` — those are project-level models and keep their names.
 */
class Program extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'programs';

    protected $fillable = [
        'code',
        'title',
        'pillar',
        'ceso_thrust',
        'description',
        'goals',
        'annual_target_hours',
        'annual_target_budget',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'annual_target_hours' => 'decimal:2',
        'annual_target_budget' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Extension program {$eventName}");
    }

    /**
     * The projects delivered under this program (§3.1).
     *
     * WIRED IN R2 — the project side is now `ExtensionProject`
     * (`extension_projects`) with a `program_id` FK. See the R2b migration.
     */
    public function projects()
    {
        return $this->hasMany(ExtensionProject::class, 'program_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Auto-generated program code: PROG-{year}-{seq}, race-safe via the
     * SequenceService floor pattern (mirrors ExtensionProject::nextCode()).
     */
    public static function nextCode(?int $year = null): string
    {
        $year ??= (int) now()->format('Y');

        $prefix = "PROG-{$year}-";

        $floor = static::withTrashed()
            ->where('code', 'like', $prefix.'%')
            ->pluck('code')
            ->map(fn (string $code) => (int) substr($code, strlen($prefix)))
            ->max();

        $seq = app(SequenceService::class)->next('program_'.$year, $floor);

        return sprintf('PROG-%d-%03d', $year, $seq);
    }
}
