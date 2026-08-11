<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\ActivityLogPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLogPhoto>
 */
class ActivityLogPhotoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'activity_log_id' => ActivityLog::factory(),
            'disk' => 'public',
            'path' => 'activity-logs/'.fake()->uuid().'.jpg',
            'original_name' => fake()->word().'.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => fake()->numberBetween(10_000, 1_000_000),
            'sort_order' => 0,
        ];
    }
}
