<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsNeededMedicationUsage extends Model
{
    use HasFactory;

    protected $fillable = ['checkin_item_id', 'user_id', 'used_at', 'note'];

    protected function casts(): array
    {
        return ['used_at' => 'datetime'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(CheckinItem::class, 'checkin_item_id');
    }
}
