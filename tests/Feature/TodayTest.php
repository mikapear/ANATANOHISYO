<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TodayTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_today_page_with_empty_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('今日を確認する');
        $response->assertSee('今日のやることはまだありません');
        $response->assertSee('できたことは、ここに少しずつ増えていきます');
        $response->assertSee('今日の予定を一緒に確認しましょう');
        $response->assertDontSee('>0<', false);
    }

    public function test_today_page_shows_only_authenticated_users_data(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-11 10:00:00', 'Asia/Tokyo'));

        $user = User::factory()->create(['name' => '本人']);
        $other = User::factory()->create(['name' => '他人']);

        Todo::factory()->for($user)->create([
            'title' => '本人の今日のTodo',
            'due_date' => '2026-08-11',
            'due_time' => '15:00:00',
            'is_completed' => false,
        ]);

        Todo::factory()->for($other)->create([
            'title' => '他人の今日のTodo',
            'due_date' => '2026-08-11',
            'is_completed' => false,
        ]);

        Todo::factory()->for($user)->completed()->create([
            'title' => '他人には見せない完了Todo',
            'due_date' => '2026-08-10',
            'completed_at' => Carbon::parse('2026-08-10 18:00:00', 'Asia/Tokyo'),
        ]);

        Todo::factory()->for($user)->completed()->create([
            'title' => '今日完了したTodo',
            'due_date' => '2026-08-11',
            'completed_at' => Carbon::parse('2026-08-11 09:00:00', 'Asia/Tokyo'),
        ]);

        Todo::factory()->for($user)->create([
            'title' => '期限超過Todo',
            'due_date' => '2026-08-10',
            'is_completed' => false,
        ]);

        ActivityLog::factory()->for($user)->create([
            'title' => '本人の記録',
            'performed_on' => '2026-08-11',
            'performed_at' => '11:30:00',
            'duration_minutes' => 45,
        ]);

        ActivityLog::factory()->for($other)->create([
            'title' => '他人の記録',
            'performed_on' => '2026-08-11',
        ]);

        Project::factory()->for($user)->active()->create(['name' => '本人のプロジェクト']);
        Project::factory()->for($other)->active()->create(['name' => '他人のプロジェクト']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('本人の今日のTodo');
        $response->assertSee('今日完了したTodo');
        $response->assertSee('期限超過Todo');
        $response->assertDontSee('本人の記録');
        $response->assertDontSee('45分');
        $response->assertSee('本人のプロジェクト');
        $response->assertDontSee('他人の今日のTodo');
        $response->assertDontSee('他人には見せない完了Todo');
        $response->assertDontSee('他人の記録');
        $response->assertDontSee('他人のプロジェクト');

        Carbon::setTestNow();
    }

    public function test_today_page_groups_todos_and_condition_logs_by_project(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-26 09:00:00', 'Asia/Tokyo'));

        $user = User::factory()->create();
        $project = Project::factory()->for($user)->active()->create([
            'name' => '治療日誌',
            'uses_todos' => true,
            'uses_activity_logs' => true,
        ]);

        Todo::factory()->for($user)->create([
            'project_id' => $project->id,
            'title' => '今日の治療準備',
            'due_date' => '2026-08-26',
        ]);

        ActivityLog::factory()->for($user)->create([
            'project_id' => $project->id,
            'title' => '治療後の体調',
            'performed_on' => '2026-08-26',
            'condition' => 'hard',
            'symptoms' => ['fatigue'],
        ]);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('プロジェクトごとの今日')
            ->assertSee('治療日誌')
            ->assertSee('今日の治療準備')
            ->assertSee('治療後の体調')
            ->assertSee('つらい');

        Carbon::setTestNow();
    }

    public function test_overdue_today_without_time_is_not_overdue(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-11 23:00:00', 'Asia/Tokyo'));

        $user = User::factory()->create();

        Todo::factory()->for($user)->create([
            'title' => '今日期限・時刻なし',
            'due_date' => '2026-08-11',
            'due_time' => null,
            'is_completed' => false,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('今日期限・時刻なし');
        $response->assertSee('今日やること');
        $response->assertSee('期限を過ぎているものはありません');

        Carbon::setTestNow();
    }
}


