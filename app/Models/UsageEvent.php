<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UsageEvent extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'event_name', 'subject_type', 'subject_id', 'context', 'occurred_at',
    ];

    protected function casts(): array
    {
        return ['context' => 'array', 'occurred_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
