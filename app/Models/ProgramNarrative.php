<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ProgramNarrative extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'extension_project_id',
        'generated_by',
        'status',
        'summary',
        'health_label',
        'risks',
        'recommendations',
        'raw_extracted_data',
        'confidence_score',
        'error_message',
        'metadata',
        'generated_at',
    ];

    protected $casts = [
        'risks' => 'array',
        'recommendations' => 'array',
        'raw_extracted_data' => 'array',
        'metadata' => 'array',
        'confidence_score' => 'decimal:2',
        'generated_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Program narrative {$eventName}");
    }

    public function program()
    {
        return $this->belongsTo(ExtensionProject::class, 'extension_project_id');
    }

    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
