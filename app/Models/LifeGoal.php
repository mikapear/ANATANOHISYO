<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LifeGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'project_id', 'category', 'title', 'target_amount', 'target_unit',
        'time_of_day', 'schedule_type', 'weekdays', 'starts_on', 'ends_on',
        'decided_with', 'note', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'weekdays' => 'array',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
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

    public function entries(): HasMany
    {
        return $this->hasMany(LifeGoalEntry::class);
    }

    public function isScheduledFor(CarbonInterface $date): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_on && $date->lt($this->starts_on)) {
            return false;
        }

        if ($this->ends_on && $date->gt($this->ends_on)) {
            return false;
        }

        return $this->schedule_type !== 'weekdays'
            || in_array($date->dayOfWeek, $this->weekdays ?? [], true);
    }
}
