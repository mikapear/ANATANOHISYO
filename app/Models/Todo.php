<?php

namespace App\Models;

use App\Policies\TodoPolicy;
use Database\Factories\TodoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_id',
    'project_id',
    'recurrence_parent_id',
    'title',
    'memo',
    'due_date',
    'due_time',
    'priority',
    'is_completed',
    'completed_at',
    'recurrence',
    'remind_at',
])]
#[UseFactory(TodoFactory::class)]
#[UsePolicy(TodoPolicy::class)]
class Todo extends Model
{
    /** @use HasFactory<TodoFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'is_completed' => 'boolean',
            'completed_at' => 'datetime',
            'remind_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return HasMany<ActivityLog, $this>
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * @return BelongsTo<Todo, $this>
     */
    public function recurrenceParent(): BelongsTo
    {
        return $this->belongsTo(Todo::class, 'recurrence_parent_id');
    }

    /**
     * @return HasOne<Todo, $this>
     */
    public function recurrenceChild(): HasOne
    {
        return $this->hasOne(Todo::class, 'recurrence_parent_id');
    }
}
