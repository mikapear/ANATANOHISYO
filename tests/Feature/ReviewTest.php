<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\CheckinEntry;
use App\Models\CheckinItem;
use App\Models\LifeGoal;
use App\Models\LifeGoalEntry;
use App\Models\Project;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_reviews(): void
    {
        $this->get('/reviews')->assertRedirect(route('login'));
    }

    public function test_weekly_review_summarizes_health_and_life_records(): void
    {
        Carbon::setTestNow('2026-09-02 12:00:00');
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->active()->create([
            'uses_checkins' => true,
        ]);
        $medication = CheckinItem::create([
            'project_id' => $project->id,
            'title' => '朝夕の薬',
            'kind' => 'medication',
            'medication_timings' => ['morning', 'evening'],
            'schedule_type' => 'daily',
            'is_active' => true,
        ]);
        foreach (['morning', 'evening'] as $timing) {
            CheckinEntry::create([
                'checkin_item_id' => $medication->id,
                'user_id' => $user->id,
                'checked_on' => '2026-09-02',
                'timing' => $timing,
            ]);
        }

        $exercise = $this->makeGoal($user, 'exercise', '10分歩く', '分');
        $nutrition = $this->makeGoal($user, 'nutrition', '水分をとる', 'ml');
        $restGoal = $this->makeGoal($user, 'other', '生活を整える');

        LifeGoalEntry::create([
            'life_goal_id' => $exercise->id,
            'user_id' => $user->id,
            'recorded_on' => '2026-09-02',
            'status' => 'partial',
            'actual_amount' => 5,
            'note' => 'ゆっくり歩いた',
        ]);
        LifeGoalEntry::create([
            'life_goal_id' => $nutrition->id,
            'user_id' => $user->id,
            'recorded_on' => '2026-09-02',
            'status' => 'completed',
            'actual_amount' => 800,
            'nutrition_type' => 'hydration',
            'food_details' => '水と麦茶',
        ]);
        LifeGoalEntry::create([
            'life_goal_id' => $restGoal->id,
            'user_id' => $user->id,
            'recorded_on' => '2026-09-02',
            'status' => 'rest',
        ]);
        ActivityLog::create([
            'user_id' => $user->id,
            'title' => '体調記録',
            'performed_on' => '2026-09-02',
            'condition' => 'hard',
        ]);
        Todo::factory()->for($user)->create([
            'title' => 'できた予定',
            'is_completed' => true,
            'completed_at' => '2026-09-02 11:00:00',
        ]);

        $this->actingAs($user)->get(route('reviews.index', [
            'period' => 'week',
            'date' => '2026-09-02',
        ]))
            ->assertOk()
            ->assertSee('8月31日〜9月6日')
            ->assertSee('2 / 14回')
            ->assertSee('3 / 21件')
            ->assertSee('少しできた')
            ->assertSee('休んだ')
            ->assertSee('合計 5分')
            ->assertSee('食事・水分記録')
            ->assertSee('つらい日も記録に残せましたね');
    }

    public function test_monthly_review_does_not_include_other_users_records(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        ActivityLog::create([
            'user_id' => $user->id,
            'title' => '本人の記録',
            'performed_on' => '2026-09-10',
            'condition' => 'good',
        ]);
        ActivityLog::create([
            'user_id' => $other->id,
            'title' => '他人の記録',
            'performed_on' => '2026-09-10',
            'condition' => 'hard',
        ]);

        $this->actingAs($user)->get(route('reviews.index', [
            'period' => 'month',
            'date' => '2026-09-10',
        ]))
            ->assertOk()
            ->assertSee('2026年9月')
            ->assertSee('1か月')
            ->assertSee('よい')
            ->assertDontSee('つらい日も記録に残せましたね');
    }

    private function makeGoal(User $user, string $category, string $title, ?string $unit = null): LifeGoal
    {
        return LifeGoal::create([
            'user_id' => $user->id,
            'category' => $category,
            'title' => $title,
            'target_unit' => $unit,
            'time_of_day' => 'anytime',
            'schedule_type' => 'daily',
            'decided_with' => 'self',
            'is_active' => true,
        ]);
    }
}