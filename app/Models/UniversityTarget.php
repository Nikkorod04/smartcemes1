<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * The university-wide annual target row (revision §4.7, R-Q3, Phase R4).
 *
 * One row per academic year (the AY START year — 2026 means AY 2026-2027).
 *
 * This is the SOLE input to the annual target (§2.2B). Per-project targets live
 * on `ExtensionProject::annual_target_hours` / `.annual_target_budget` and are
 * planning figures for their own project — they are deliberately NOT summed to
 * produce this number. Broad programs carry no target at all.
 *
 * The relationship is a CONSUMPTION model: this target is a pool that project
 * actuals are subtracted from:
 *
 *     remaining = annual_target_hours - SUM(project.actual_training_hours)
 *
 * Actuals are computed live by `TrainingHoursService` and never stored here.
 */
class UniversityTarget extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'year',
        'label',
        'annual_target_hours',
        'annual_target_budget',
        'notes',
        'updated_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'annual_target_hours' => 'decimal:2',
        'annual_target_budget' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "University target {$eventName}");
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * The label the UI shows. Stored when set, so the Director controls the
     * convention; otherwise derived as "AY 2026-2027".
     */
    public function getDisplayLabelAttribute(): string
    {
        return $this->label ?: sprintf('AY %d-%d', $this->year, $this->year + 1);
    }

    /**
     * The row for a given AY start year, or null when the Director has not set
     * one. Deliberately does NOT create a row: a missing target must render as
     * "no target set", never as a silent 0 that makes attainment look infinite.
     */
    public static function forYear(int $year): ?self
    {
        return static::query()->where('year', $year)->first();
    }

    /** Most recent target year on record — the page's default selection. */
    public static function current(): ?self
    {
        return static::query()->orderByDesc('year')->first();
    }
}
