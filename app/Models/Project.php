<?php

namespace App\Models;

use App\Policies\ProjectPolicy;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'name',
    'description',
    'start_date',
    'due_date',
    'status',
    'template',
    'uses_todos',
    'uses_checkins',
    'uses_activity_logs',
    'uses_calendar',
])]
#[UseFactory(ProjectFactory::class)]
#[UsePolicy(ProjectPolicy::class)]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'uses_todos' => 'boolean',
            'uses_checkins' => 'boolean',
            'uses_activity_logs' => 'boolean',
            'uses_calendar' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function checkinItems(): HasMany
    {
        return $this->hasMany(CheckinItem::class)->orderBy('position')->orderBy('id');
    }

    public function lifeGoals(): HasMany
    {
        return $this->hasMany(LifeGoal::class);
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(Treatment::class);
    }
}
