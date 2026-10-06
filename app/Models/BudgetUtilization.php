<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BudgetUtilization extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'extension_project_id',
        'activity_id',
        'item_name',
        'description',
        'amount',
        'date_used',
        'receipt_reference',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date_used' => 'date',
    ];

    public function program()
    {
        return $this->belongsTo(ExtensionProject::class, 'extension_project_id');
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }
}
