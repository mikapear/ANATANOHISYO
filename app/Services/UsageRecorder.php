<?php

namespace App\Services;

use App\Models\UsageEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class UsageRecorder
{
    public function record(User $user, string $eventName, ?Model $subject = null, array $context = []): void
    {
        try {
            UsageEvent::query()->create([
                'user_id' => $user->id,
                'event_name' => $eventName,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'context' => $context === [] ? null : $context,
                'occurred_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
