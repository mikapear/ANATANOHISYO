<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\LifeGoal;
use App\Models\LifeGoalEntry;
use App\Models\Project;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_calendar(): void
    {
        $this->get('/calendar')->assertRedirect(route('login'));
    }

    public function test_authenticated_home_redirects_to_calendar(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertRedirect(route('calendar.index'));
    }

    public function test_user_can_view_requested_month(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/calendar?month=2026-08')
            ->assertOk()
            ->assertSee('2026年8月')
            ->assertSee('日付を選ぶと');
    }

    public function test_calendar_selects_today_and_shows_goal_result_by_default(): void
    {
        Carbon::setTestNow('2026-08-25 09:00:00');
        $user = User::factory()->create();
        $goal = LifeGoal::create([
            'user_id' => $user->id,
            'category' => 'exercise',
            'title' => '朝に10分歩く',
            'target_unit' => '分',
            'time_of_day' => 'morning',
            'schedule_type' => 'daily',
            'decided_with' => 'self',
            'is_active' => true,
        ]);
        LifeGoalEntry::create([
            'life_goal_id' => $goal->id,
            'user_id' => $user->id,
            'recorded_on' => '2026-08-25',
            'status' => 'partial',
            'actual_amount' => 5,
            'note' => 'ゆっくり歩いた',
        ]);

        $this->actingAs($user)->get(route('calendar.index'))
            ->assertOk()
            ->assertSee('8月25日')
            ->assertSee('目標 1/1')
            ->assertSee('朝に10分歩く')
            ->assertSee('少しできた')
            ->assertSee('5分')
            ->assertSee('ゆっくり歩いた')
            ->assertSee('今日の詳しい確認へ');

        Carbon::setTestNow();
    }

    public function test_selected_date_shows_own_todos_and_logs_only(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Todo::factory()->for($user)->create(['title' => '本人の予定', 'due_date' => '2026-08-25']);
        ActivityLog::factory()->for($user)->create(['title' => '本人の記録', 'performed_on' => '2026-08-25']);
        Todo::factory()->for($other)->create(['title' => '他人の予定', 'due_date' => '2026-08-25']);
        ActivityLog::factory()->for($other)->create(['title' => '他人の記録', 'performed_on' => '2026-08-25']);

        $this->actingAs($user)->get('/calendar?month=2026-08&date=2026-08-25')
            ->assertOk()
            ->assertSee('8月25日')
            ->assertSee('本人の予定')
            ->assertSee('本人の記録')
            ->assertDontSee('他人の予定')
            ->assertDontSee('他人の記録');
    }

    public function test_calendar_can_filter_projects_and_show_condition(): void
    {
        $user = User::factory()->create();
        $first = Project::factory()->for($user)->create(['name' => '治療日誌', 'uses_calendar' => true]);
        $second = Project::factory()->for($user)->create(['name' => '旅行計画', 'uses_calendar' => true]);
        Todo::factory()->for($user)->create(['project_id' => $first->id, 'title' => '治療の予定', 'due_date' => '2026-08-25']);
        Todo::factory()->for($user)->create(['project_id' => $second->id, 'title' => '旅行の予定', 'due_date' => '2026-08-25']);
        ActivityLog::factory()->for($user)->create([
            'project_id' => $first->id,
            'title' => '治療後の記録',
            'performed_on' => '2026-08-25',
            'condition' => 'hard',
            'symptoms' => ['fatigue'],
        ]);

        $this->actingAs($user)->get('/calendar?month=2026-08&date=2026-08-25&project_id='.$first->id)
            ->assertOk()
            ->assertSee('治療の予定')
            ->assertSee('治療後の記録')
            ->assertSee('つらい')
            ->assertSee('症状 1件')
            ->assertDontSee('旅行の予定');
    }

    public function test_invalid_month_falls_back_safely(): void
    {
        Carbon::setTestNow('2026-08-25');
        $user = User::factory()->create();
        $this->actingAs($user)->get('/calendar?month=invalid')->assertOk()->assertSee('2026年8月');
        Carbon::setTestNow();
    }

    public function test_calendar_date_is_prefilled_in_creation_forms(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/todos/create?due_date=2026-08-25')
            ->assertOk()->assertSee('value="2026-08-25"', false);
        $this->get('/activity-logs/create?performed_on=2026-08-25')
            ->assertOk()->assertSee('value="2026-08-25"', false);
    }
}