<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A single tagged expertise area for a faculty member (revision §4.5 /
 * Phase R3).
 *
 * One row per area — see the migration docblock for why this is a table rather
 * than a JSON column on `faculties` (the module filters and counts by area).
 *
 * No soft deletes here: an area the user removed from the multi-select should
 * genuinely disappear. The (faculty_id, area) unique index is the guard against
 * duplicates.
 */
class FacultyExpertise extends Model
{
    use HasFactory;

    /**
     * Laravel would pluralise "FacultyExpertise" to "faculty_expertises";
     * the agreed table name is irregular, so it is stated explicitly.
     */
    protected $table = 'faculty_expertise';

    protected $fillable = [
        'faculty_id',
        'area',
        'category',
    ];

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    /**
     * Stable ordering for profile lists and filter dropdowns.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('area');
    }
}
