<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_assignment_id', 'survey_question_id', 'response', 'answered_at',
    ];

    protected function casts(): array
    {
        return ['response' => 'array', 'answered_at' => 'datetime'];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(SurveyAssignment::class, 'survey_assignment_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(SurveyQuestion::class, 'survey_question_id');
    }
}
