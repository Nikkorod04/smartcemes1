<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProgramObjective extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'extension_program_id',
        'objective',
        'kpi_metric',
        'baseline_value',
        'target_value',
        'actual_value',
        'unit',
        'target_date',
        'status',
        'evidence_notes',
    ];

    protected $casts = [
        'baseline_value' => 'decimal:4',
        'target_value' => 'decimal:4',
        'actual_value' => 'decimal:4',
        'target_date' => 'date',
    ];

    public const KPI_METRICS = [
        'participation_rate',
        'activity_completion_rate',
        'attendance_consistency',
        'budget_utilization',
        'knowledge_gain',
        'cost_per_beneficiary',
        'community_reach',
    ];

    public function program()
    {
        return $this->belongsTo(ExtensionProgram::class, 'extension_program_id');
    }

    public function isQualitative(): bool
    {
        return $this->kpi_metric === null;
    }
}
