<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_definition_id', 'key', 'prompt', 'response_type', 'options',
        'is_required', 'position',
    ];

    protected function casts(): array
    {
        return ['options' => 'array', 'is_required' => 'boolean', 'position' => 'integer'];
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
