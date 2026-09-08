<?php

namespace App\Services;

use App\Models\UsageEvent;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class UsageAnalytics
{
    public function summarize(User $user, ?CarbonInterface $asOf = null): array
    {
        $timezone = config('app.timezone');
        $end = $asOf
            ? CarbonImmutable::instance($asOf)->setTimezone($timezone)->endOfDay()
            : CarbonImmutable::now($timezone)->endOfDay();
        $start = $end->subDays(55)->startOfDay();

        $firstEvent = UsageEvent::query()
            ->where('user_id', $user->id)
            ->oldest('occurred_at')
            ->oldest('id')
            ->first(['occurred_at']);
        $lastEvent = UsageEvent::query()
            ->where('user_id', $user->id)
            ->latest('occurred_at')
            ->latest('id')
            ->first(['occurred_at']);
        $events = UsageEvent::query()
            ->where('user_id', $user->id)
            ->whereBetween('occurred_at', [$start, $end])
            ->orderBy('occurred_at')
            ->get(['event_name', 'occurred_at']);

        return [
            'first_used_at' => $firstEvent?->occurred_at,
            'last_used_at' => $lastEvent?->occurred_at,
            'windows' => [
                '7_days' => $this->window($events, $end, 7),
                '28_days' => $this->window($events, $end, 28),
                '8_weeks' => $this->window($events, $end, 56),
            ],
            'consecutive_active_weeks' => $this->consecutiveActiveWeeks($events),
        ];
    }

    private function window(Collection $events, CarbonImmutable $end, int $days): array
    {
        $start = $end->subDays($days - 1)->startOfDay();
        $periodEvents = $events->filter(
            fn (UsageEvent $event) => $event->occurred_at->betweenIncluded($start, $end)
        );

        return [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'active_days' => $periodEvents
                ->map(fn (UsageEvent $event) => $event->occurred_at->toDateString())
                ->unique()
                ->count(),
            'event_counts' => $periodEvents
                ->countBy('event_name')
                ->sortKeys()
                ->all(),
        ];
    }

    private function consecutiveActiveWeeks(Collection $events): int
    {
        $weeks = $events
            ->map(fn (UsageEvent $event) => $event->occurred_at
                ->toImmutable()
                ->startOfWeek(CarbonInterface::MONDAY)
                ->toDateString())
            ->unique()
            ->sortDesc()
            ->values();

        if ($weeks->isEmpty()) {
            return 0;
        }

        $expected = CarbonImmutable::parse($weeks->first());
        $count = 0;

        foreach ($weeks as $week) {
            if ($week !== $expected->toDateString()) {
                break;
            }

            $count++;
            $expected = $expected->subWeek();
        }

        return $count;
    }
}
