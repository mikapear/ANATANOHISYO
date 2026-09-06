<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LifeGoalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'life_goal_id', 'user_id', 'recorded_on', 'status', 'actual_amount',
        'nutrition_type', 'food_details', 'note',
    ];

    protected function casts(): array
    {
        return ['recorded_on' => 'date', 'actual_amount' => 'decimal:2'];
    }

    public function lifeGoal(): BelongsTo
    {
        return $this->belongsTo(LifeGoal::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}