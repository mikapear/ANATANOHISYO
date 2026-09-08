<?php

namespace Tests\Feature;

use App\Models\UsageEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UsageDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_usage_dashboard(): void
    {
        $this->get(route('usage.index'))->assertRedirect(route('login'));
    }

    public function test_user_can_view_own_usage_summary(): void
    {
        Carbon::setTestNow('2026-09-09 12:00:00');
        $user = User::factory()->create();
        UsageEvent::create(['user_id' => $user->id, 'event_name' => 'today.view', 'occurred_at' => '2026-09-08 09:00:00']);
        UsageEvent::create(['user_id' => $user->id, 'event_name' => 'todo.completed', 'occurred_at' => '2026-09-09 10:00:00']);

        $this->actingAs($user)->get(route('usage.index'))->assertOk()->assertSee('利用状況')->assertSee('利用があった日 / 7日')->assertSee('予定を完了した')->assertSee('1回');
    }

    public function test_dashboard_does_not_include_another_users_events(): void
    {
        Carbon::setTestNow('2026-09-09 12:00:00');
        $user = User::factory()->create();
        $other = User::factory()->create();
        UsageEvent::create(['user_id' => $other->id, 'event_name' => 'todo.completed', 'occurred_at' => '2026-09-09 10:00:00']);

        $this->actingAs($user)->get(route('usage.index'))->assertOk()->assertDontSee('予定を完了した')->assertSee('まだ利用記録はありません');
    }
}
