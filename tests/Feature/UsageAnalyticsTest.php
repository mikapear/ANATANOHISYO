<?php

namespace Tests\Feature;

use App\Models\UsageEvent;
use App\Models\User;
use App\Services\UsageAnalytics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UsageAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_summarizes_usage_windows_and_event_counts(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->event($user, 'today.view', '2026-09-01 08:00:00');
        $this->event($user, 'today.view', '2026-09-01 12:00:00');
        $this->event($user, 'todo.completed', '2026-09-06 20:00:00');
        $this->event($user, 'medication.checked', '2026-09-07 23:59:59');
        $this->event($user, 'goal.recorded', '2026-08-11 09:00:00');
        $this->event($user, 'today.view', '2026-07-01 09:00:00');
        $this->event($other, 'today.view', '2026-09-07 10:00:00');

        $summary = app(UsageAnalytics::class)->summarize(
            $user,
            Carbon::parse('2026-09-07 12:00:00')
        );

        $this->assertSame('2026-07-01 09:00:00', $summary['first_used_at']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-07 23:59:59', $summary['last_used_at']->format('Y-m-d H:i:s'));
        $this->assertSame(3, $summary['windows']['7_days']['active_days']);
        $this->assertSame([
            'medication.checked' => 1,
            'today.view' => 2,
            'todo.completed' => 1,
        ], $summary['windows']['7_days']['event_counts']);
        $this->assertSame(4, $summary['windows']['28_days']['active_days']);
        $this->assertSame(4, $summary['windows']['8_weeks']['active_days']);
    }

    public function test_it_counts_consecutive_active_weeks_from_the_latest_active_week(): void
    {
        $user = User::factory()->create();
        $this->event($user, 'today.view', '2026-09-06 12:00:00');
        $this->event($user, 'today.view', '2026-08-26 12:00:00');
        $this->event($user, 'today.view', '2026-08-20 12:00:00');
        $this->event($user, 'today.view', '2026-08-01 12:00:00');

        $summary = app(UsageAnalytics::class)->summarize(
            $user,
            Carbon::parse('2026-09-07 12:00:00')
        );

        $this->assertSame(3, $summary['consecutive_active_weeks']);
    }

    public function test_user_without_events_has_an_empty_summary(): void
    {
        $summary = app(UsageAnalytics::class)->summarize(
            User::factory()->create(),
            Carbon::parse('2026-09-07 12:00:00')
        );

        $this->assertNull($summary['first_used_at']);
        $this->assertNull($summary['last_used_at']);
        $this->assertSame(0, $summary['windows']['7_days']['active_days']);
        $this->assertSame([], $summary['windows']['7_days']['event_counts']);
        $this->assertSame(0, $summary['consecutive_active_weeks']);
    }

    private function event(User $user, string $name, string $occurredAt): UsageEvent
    {
        return UsageEvent::create([
            'user_id' => $user->id,
            'event_name' => $name,
            'occurred_at' => $occurredAt,
        ]);
    }
}
