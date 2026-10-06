<?php

namespace App\Models;

use Database\Factories\FacultyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Faculty extends Model
{
    /** @use HasFactory<FacultyFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'employee_id',
        'college_id',
        'department',
        'specialization',
        'position',
        'status',
        'avatar',
        'phone',
        'address',
        'notes',
    ];

    /**
     * Faculty status vocabulary (revision §5 R3 step 3).
     */
    public const STATUS_ACTIVE = 'active';

    public const STATUS_ON_LEAVE = 'on_leave';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_ON_LEAVE,
        self::STATUS_INACTIVE,
    ];

    /**
     * Human labels for the UI. The prototype shows "On Leave" and "Active";
     * the stored value is snake_case so it is filter-safe.
     */
    public const STATUS_LABELS = [
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_ON_LEAVE => 'On Leave',
        self::STATUS_INACTIVE => 'Inactive',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The faculty member's college (revision §4.5 / Phase R3).
     *
     * Nullable by design: the column is backfilled from `department` by the
     * R3a migration, but a faculty member whose department is unrecognised — or
     * one created before the college is chosen — legitimately has no college.
     * Callers must handle null (the UI shows an em dash and offers an edit).
     */
    public function college()
    {
        return $this->belongsTo(College::class);
    }

    /**
     * Tagged expertise areas (revision §4.5) — one row per area.
     *
     * Powers the Faculty Directory expertise filter and the "most active
     * faculty" facets. Ordered by area so the profile list is stable.
     */
    public function expertise()
    {
        return $this->hasMany(FacultyExpertise::class)->orderBy('area');
    }

    /**
     * area => category, built from `config('smartcemes.expertise_categories')`.
     *
     * Lives on the model rather than in a component because TWO callers write
     * expertise — the admin's Faculty Directory and a faculty member's own
     * profile — and they must file an area under the same category. It was a
     * private method on the Directory before 2026-09-27, which meant the second
     * caller could have silently disagreed with the first.
     *
     * An area absent from the config map keeps a NULL category, which is
     * honest — the UI shows those under "Other".
     *
     * @return array<string, string>
     */
    public static function expertiseCategoryMap(): array
    {
        $map = [];

        foreach (config('smartcemes.expertise_categories', []) as $category => $areas) {
            foreach ($areas as $area) {
                $map[$area] = $category;
            }
        }

        return $map;
    }

    /**
     * Replace this faculty member's expertise set in one call.
     *
     * Used by the profile form's multi-select, which submits the full desired
     * set each time. Deleting the rows that fell out and inserting the new ones
     * keeps the (faculty_id, area) unique index satisfied without a
     * read-then-write race.
     *
     * @param  array<int, string>  $areas
     * @param  array<string, string>  $categories  area => category
     */
    public function syncExpertise(array $areas, array $categories = []): void
    {
        $areas = array_values(array_unique(array_filter(array_map(
            fn ($area) => trim((string) $area),
            $areas
        ))));

        $this->expertise()->whereNotIn('area', $areas ?: [''])->delete();

        $existing = $this->expertise()->pluck('area')->all();

        foreach ($areas as $area) {
            if (in_array($area, $existing, true)) {
                // Keep the category fresh without touching the unique key.
                $this->expertise()->where('area', $area)->update([
                    'category' => $categories[$area] ?? null,
                ]);

                continue;
            }

            $this->expertise()->create([
                'area' => $area,
                'category' => $categories[$area] ?? null,
            ]);
        }
    }

    public function activities()
    {
        return $this->belongsToMany(Activity::class, 'activity_faculty');
    }

    public function availabilityRequests()
    {
        return $this->hasMany(AvailabilityRequest::class);
    }

    public function activityProposals()
    {
        return $this->hasMany(ActivityProposal::class);
    }

    public function renderedHours()
    {
        return $this->hasMany(RenderedHours::class);
    }

    public function ledPrograms()
    {
        return $this->hasMany(ExtensionProject::class, 'program_lead_id');
    }

    /**
     * The display label for the status column ("On Leave", not "on_leave").
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isOnLeave(): bool
    {
        return $this->status === self::STATUS_ON_LEAVE;
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Filter by college code (the board's ?college= deep link).
     */
    public function scopeForCollegeCode($query, ?string $code)
    {
        if ($code === null || $code === '' || strtoupper($code) === 'ALL') {
            return $query;
        }

        return $query->whereHas('college', fn ($q) => $q->where('code', strtoupper($code)));
    }
}
