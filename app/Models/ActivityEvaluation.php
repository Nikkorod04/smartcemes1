<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One anonymous public evaluation of an activity (Phase 2).
 *
 * No beneficiary, no name, no contact detail: the form is filled in at the end
 * of a session and the office only ever sees the aggregate and the comments.
 */
class ActivityEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_id',
        'activity_evaluation_link_id',
        'client_uuid',
        'satisfaction',
        'comments',
        'captured_at',
    ];

    protected $casts = [
        'satisfaction' => 'integer',
        'captured_at' => 'datetime',
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function link()
    {
        return $this->belongsTo(ActivityEvaluationLink::class, 'activity_evaluation_link_id');
    }
}
