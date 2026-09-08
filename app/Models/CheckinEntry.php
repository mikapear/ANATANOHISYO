<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckinEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'checkin_item_id', 'user_id', 'checked_on', 'timing', 'status', 'confirmed_at', 'note',
    ];

    protected function casts(): array
    {
        return ['checked_on' => 'date', 'confirmed_at' => 'datetime'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(CheckinItem::class, 'checkin_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
