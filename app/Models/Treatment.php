<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Treatment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'project_id', 'name', 'treatment_type', 'scheduled_on',
        'scheduled_at', 'cycle_number', 'hospital', 'department', 'note', 'status',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_on' => 'date',
            'cycle_number' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}