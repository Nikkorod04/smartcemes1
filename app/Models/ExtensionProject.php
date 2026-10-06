<?php

namespace App\Models;

use App\Services\SequenceService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * An EXTENSION PROJECT — the entity that carries activities.
 *
 * RENAMED IN PHASE R2 from `ExtensionProject` (table `extension_projects`).
 * The old name was misleading: this is not a "program" in the revised
 * hierarchy, it is the project level:
 *
 *     College -> Program -> Project -> Activity
 *
 * The broad CESO-thrust level is `App\Models\Program` (table `programs`),
 * introduced in R1. See the R2a migration docblock for why the broad level was
 * NOT renamed to `extension_projects`.
 *
 * `program_id` is therefore the BROAD parent, and `college_id` the college —
 * both added by the R2b migration and backfilled for the six inherited rows.
 */
class ExtensionProject extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'extension_projects';

    protected $fillable = [
        'college_id',
        'program_id',
        'code',
        'title',
        'description',
        'goals',
        'objectives',
        'planned_start_date',
        'planned_end_date',
        'target_beneficiaries',
        'beneficiary_categories',
        'allocated_budget',
        'annual_target_hours',
        'annual_target_budget',
        'program_lead_id',
        'partners',
        'cover_image',
        'gallery_images',
        'attachments',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
        'beneficiary_categories' => 'array',
        'partners' => 'array',
        'gallery_images' => 'array',
        'attachments' => 'array',
        'allocated_budget' => 'decimal:2',
        'annual_target_hours' => 'decimal:2',
        'annual_target_budget' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Extension project {$eventName}");
    }

    /* ------------------------------------------------------------------ */
    /* Hierarchy */
    /* ------------------------------------------------------------------ */

    /** The college this project belongs to. */
    public function college()
    {
        return $this->belongsTo(College::class);
    }

    /** The BROAD program (CESO thrust) this project sits under. */
    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function programLead()
    {
        return $this->belongsTo(Faculty::class, 'program_lead_id');
    }

    /* ------------------------------------------------------------------ */
    /* Children */
    /* ------------------------------------------------------------------ */

    public function activities()
    {
        return $this->hasMany(Activity::class, 'extension_project_id');
    }

    /**
     * 6.15 ProgramObjective relation. Named deliberately to avoid shadowing
     * by the `objectives` TEXT column (6.4 high-level narrative summary).
     *
     * Retained unread per R-Q2 — soft-deprecated in Phase R4, not deleted here.
     */
    public function programObjectives()
    {
        return $this->hasMany(ProgramObjective::class, 'extension_project_id');
    }

    public function budgetUtilizations()
    {
        return $this->hasMany(BudgetUtilization::class, 'extension_project_id');
    }

    public function communities()
    {
        return $this->belongsToMany(Community::class, 'community_extension_project');
    }

    public function beneficiaries()
    {
        return $this->belongsToMany(Beneficiary::class, 'extension_project_beneficiary');
    }

    public function programNarratives()
    {
        return $this->hasMany(ProgramNarrative::class, 'extension_project_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* ------------------------------------------------------------------ */
    /* Derived values */
    /* ------------------------------------------------------------------ */

    public function utilizedBudget(): float
    {
        return (float) $this->budgetUtilizations()->sum('amount');
    }

    /**
     * The figure this project's budget utilization is measured against.
     *
     * Amended 2026-09-26 (owner decision): a project has ONE budget figure — its
     * ALLOCATION. There is no separate annual budget target to consume against,
     * so `allocated_budget` is the denominator on every surface.
     *
     * `annual_target_budget` is therefore RETAINED BUT UNREAD — the same
     * treatment D-R7 gives the 8.6 KPIs. The column, its cast and its fillable
     * entry stay so historical rows remain inspectable and the migration stays
     * reversible, but nothing reads it and no new reader may be added.
     *
     * Training HOURS keep a real annual target at project level
     * (`annual_target_hours`, see hoursTarget()) with the university pool above.
     */
    public function budgetAllocated(): float
    {
        return (float) $this->allocated_budget;
    }

    /** R4 (§4.3): the annual TRAINING-HOURS target, or NULL when none is set. */
    public function hoursTarget(): ?float
    {
        return $this->annual_target_hours !== null
            ? (float) $this->annual_target_hours
            : null;
    }

    /**
     * Is utilization past the project's allocation (D7)?
     *
     * Uses the same `budgetAllocated()` denominator as every other budget
     * surface, so the "over" badge cannot disagree with the bar beside it.
     */
    public function isOverAllocated(): bool
    {
        $allocation = $this->budgetAllocated();

        return $allocation > 0 && $this->utilizedBudget() > $allocation;
    }

    /* ------------------------------------------------------------------ */
    /* Codes */
    /* ------------------------------------------------------------------ */

    /**
     * Auto-generated project code (R-Q4 / revision §4.3).
     *
     * NEW projects adopt the COLLEGE-PREFIXED pattern — `CAS-2026-001`,
     * `COE-2026-001`, `CME-2026-001` — so a code self-identifies its college.
     *
     * MIGRATED rows keep their original `EXT-{year}-{seq}` codes untouched:
     * `nextCode()` never issues an `EXT-` code again, so the two schemes cannot
     * collide. The sequence key is per-college (`project_CAS_{year}`, …) so each
     * college numbers independently, as the code pattern implies.
     *
     * The `$floor` is computed across the three college prefixes AND the legacy
     * `EXT-` prefix for the same year, then the highest is passed to
     * SequenceService. This is what makes duplicate protection hold even if a
     * row was inserted outside the sequence (the v4.11 drift case, §9.2).
     */
    public static function nextCode(string $collegeCode, ?int $year = null): string
    {
        $year ??= (int) now()->format('Y');
        $collegeCode = strtoupper($collegeCode);

        $prefix = "{$collegeCode}-{$year}-";

        $floor = static::withTrashed()
            ->where('code', 'like', $prefix.'%')
            ->pluck('code')
            ->map(fn (string $code) => (int) substr($code, strlen($prefix)))
            ->max();

        $seq = app(SequenceService::class)->next(
            'project_'.$collegeCode.'_'.$year,
            $floor
        );

        return sprintf('%s-%d-%03d', $collegeCode, $year, $seq);
    }
}
