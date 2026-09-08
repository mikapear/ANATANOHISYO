<?php

namespace Tests\Feature;

use App\Models\CheckinItem;
use App\Models\LifeGoal;
use App\Models\Project;
use App\Models\Todo;
use App\Models\UsageEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UsageEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_today_view_is_recorded_without_unnecessary_personal_data(): void
    {
        Carbon::setTestNow('2026-09-07 09:30:00');
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $event = UsageEvent::firstOrFail();
        $this->assertSame($user->id, $event->user_id);
        $this->assertSame('today.view', $event->event_name);
        $this->assertSame('2026-09-07 09:30:00', $event->occurred_at->format('Y-m-d H:i:s'));
        $this->assertNull($event->subject_type);
        $this->assertNull($event->context);
        $this->assertFalse(Schema::hasColumn('usage_events', 'ip_address'));
        $this->assertFalse(Schema::hasColumn('usage_events', 'user_agent'));
    }

    public function test_todo_completion_is_recorded_only_when_state_changes(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->for($user)->create(['is_completed' => false]);

        $this->actingAs($user)->patch(route('todos.complete', $todo))->assertRedirect();
        $this->patch(route('todos.complete', $todo))->assertRedirect();

        $this->assertDatabaseCount('usage_events', 1);
        $this->assertDatabaseHas('usage_events', [
            'user_id' => $user->id,
            'event_name' => 'todo.completed',
            'subject_type' => Todo::class,
            'subject_id' => $todo->id,
        ]);
    }

    public function test_medication_check_is_recorded_without_duplicate_submission(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->active()->create(['uses_checkins' => true]);
        $item = CheckinItem::create([
            'project_id' => $project->id,
            'title' => '朝のお薬',
            'kind' => 'medication',
            'medication_timings' => ['morning'],
            'schedule_type' => 'daily',
            'is_active' => true,
        ]);
        $payload = ['checked_on' => '2026-09-07', 'timing' => 'morning', 'checked' => '1'];

        $this->actingAs($user)->put(route('checkin-entries.update', $item), $payload)->assertRedirect();
        $this->put(route('checkin-entries.update', $item), $payload)->assertRedirect();

        $event = UsageEvent::sole();
        $this->assertSame('medication.checked', $event->event_name);
        $this->assertSame(['status' => 'taken', 'timing' => 'morning'], $event->context);
    }

    public function test_goal_record_is_logged_only_when_the_record_changes(): void
    {
        $user = User::factory()->create();
        $goal = LifeGoal::create([
            'user_id' => $user->id,
            'category' => 'exercise',
            'title' => '5分歩く',
            'time_of_day' => 'anytime',
            'schedule_type' => 'daily',
            'decided_with' => 'self',
            'is_active' => true,
        ]);
        $payload = ['recorded_on' => '2026-09-07', 'status' => 'partial'];

        $this->actingAs($user)->put(route('goal-entries.update', $goal), $payload)->assertRedirect();
        $this->put(route('goal-entries.update', $goal), $payload)->assertRedirect();
        $this->put(route('goal-entries.update', $goal), [
            'recorded_on' => '2026-09-07',
            'status' => 'completed',
        ])->assertRedirect();

        $this->assertDatabaseCount('usage_events', 2);
        $this->assertSame(
            [['status' => 'partial'], ['status' => 'completed']],
            UsageEvent::orderBy('id')->pluck('context')->all()
        );
    }

    public function test_usage_events_are_removed_with_the_user(): void
    {
        $user = User::factory()->create();
        UsageEvent::create([
            'user_id' => $user->id,
            'event_name' => 'today.view',
            'occurred_at' => now(),
        ]);

        $user->delete();

        $this->assertDatabaseCount('usage_events', 0);
    }
}
