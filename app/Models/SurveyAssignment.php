<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'survey_definition_id', 'phase', 'due_on', 'available_from',
        'available_until', 'status', 'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_on' => 'date',
            'available_from' => 'date',
            'available_until' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(SurveyDefinition::class, 'survey_definition_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SurveyAnswer::class);
    }
}
