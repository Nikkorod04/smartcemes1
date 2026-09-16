<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Per-activity XLSX import provenance (6.19, v4.13).
 *
 * Each confirmed attendance / evaluation import archives the source file
 * and records who imported it, when, and the row counts. The imported
 * data itself lives on `attendances` (attendance) or the activity's
 * aggregate score columns (evaluation, D13).
 */
class ActivityImport extends Model
{
    use SoftDeletes;

    public const TYPE_ATTENDANCE = 'attendance';

    public const TYPE_EVALUATION = 'evaluation';

    protected $fillable = [
        'activity_id',
        'type',
        'file_path',
        'original_name',
        'imported_by',
        'imported_at',
        'rows_processed',
        'rows_applied',
        'rows_skipped',
        'summary',
    ];

    protected $casts = [
        'imported_at' => 'datetime',
        'summary' => 'array',
        'rows_processed' => 'integer',
        'rows_applied' => 'integer',
        'rows_skipped' => 'integer',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
