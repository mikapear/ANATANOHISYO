<?php

namespace App\Models;

use App\Policies\ActivityLogPhotoPolicy;
use Database\Factories\ActivityLogPhotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'activity_log_id',
    'disk',
    'path',
    'original_name',
    'mime_type',
    'size_bytes',
    'sort_order',
])]
#[UseFactory(ActivityLogPhotoFactory::class)]
#[UsePolicy(ActivityLogPhotoPolicy::class)]
class ActivityLogPhoto extends Model
{
    /** @use HasFactory<ActivityLogPhotoFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ActivityLog, $this>
     */
    public function activityLog(): BelongsTo
    {
        return $this->belongsTo(ActivityLog::class);
    }
}
