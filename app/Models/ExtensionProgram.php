<?php

namespace App\Models;

use App\Services\SequenceService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ExtensionProgram extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
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
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Extension program {$eventName}");
    }

    public function programLead()
    {
        return $this->belongsTo(Faculty::class, 'program_lead_id');
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * 6.15 ProgramObjective relation. Named deliberately to avoid shadowing
     * by the `objectives` TEXT column (6.4 high-level narrative summary).
     */
    public function programObjectives()
    {
        return $this->hasMany(ProgramObjective::class);
    }

    public function budgetUtilizations()
    {
        return $this->hasMany(BudgetUtilization::class);
    }

    public function communities()
    {
        return $this->belongsToMany(Community::class, 'community_extension_program');
    }

    public function beneficiaries()
    {
        return $this->belongsToMany(Beneficiary::class, 'extension_program_beneficiary');
    }

    public function programNarratives()
    {
        return $this->hasMany(ProgramNarrative::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function utilizedBudget(): float
    {
        return (float) $this->budgetUtilizations()->sum('amount');
    }

    public function isOverAllocated(): bool
    {
        return $this->allocated_budget > 0
            && $this->utilizedBudget() > (float) $this->allocated_budget;
    }

    /**
     * Auto-generated program code: EXT-{year}-{seq}, race-safe via the
     * employee-id sequence table pattern. The max suffix already stored
     * (seeded or manually inserted rows, soft-deleted included — the
     * unique index still applies to them) is passed as a floor so the
     * sequence can never issue a duplicate code.
     */
    public static function nextCode(?int $year = null): string
    {
        $year ??= (int) now()->format('Y');

        $prefix = "EXT-{$year}-";

        $floor = static::withTrashed()
            ->where('code', 'like', $prefix.'%')
            ->pluck('code')
            ->map(fn (string $code) => (int) substr($code, strlen($prefix)))
            ->max();

        $seq = app(SequenceService::class)->next('extension_program_'.$year, $floor);

        return sprintf('EXT-%d-%03d', $year, $seq);
    }
}
