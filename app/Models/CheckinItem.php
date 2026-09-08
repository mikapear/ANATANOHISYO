<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CheckinItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'title', 'kind', 'medication_timings',
        'dose_amount', 'dose_unit', 'medication_instructions', 'medication_precautions',
        'schedule_type', 'weekdays', 'cycle_on_days', 'cycle_rest_days', 'starts_on', 'ends_on',
        'position', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'medication_timings' => 'array',
            'dose_amount' => 'decimal:2',
            'weekdays' => 'array',
            'cycle_on_days' => 'integer',
            'cycle_rest_days' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(CheckinEntry::class);
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

        if ($this->schedule_type === 'weekdays') {
            return in_array($date->dayOfWeek, $this->weekdays ?? [], true);
        }

        if ($this->schedule_type === 'cycle') {
            if (! $this->starts_on || ! $this->cycle_on_days || ! $this->cycle_rest_days) {
                return false;
            }

            $cycleLength = $this->cycle_on_days + $this->cycle_rest_days;
            $dayInCycle = $this->starts_on->startOfDay()->diffInDays($date->startOfDay()) % $cycleLength;

            return $dayInCycle < $this->cycle_on_days;
        }

        return true;
    }

    public function scheduledSlotCount(): int
    {
        return $this->kind === 'medication'
            ? count($this->medication_timings ?? [])
            : 1;
    }
}
