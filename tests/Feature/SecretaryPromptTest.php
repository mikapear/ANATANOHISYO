<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\CheckinEntry;
use App\Models\CheckinItem;
use App\Models\LifeGoal;
use App\Models\Project;
use App\Models\Treatment;
use App\Models\UsageEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SecretaryPromptTest extends TestCase
{
    use RefreshDatabase;

    public function test_secretary_prioritizes_rest_when_condition_is_hard(): void
    {
        Carbon::setTestNow('2026-09-02 10:00:00');
        $user = User::factory()->create();
        ActivityLog::create([
            'user_id' => $user->id,
            'title' => '今日の体調',
            'performed_on' => '2026-09-02',
            'condition' => 'hard',
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('休むことも今日の大切な予定')
            ->assertSee('医療者に相談');
    }

    public function test_secretary_reminds_user_about_unchecked_medication(): void
    {
        Carbon::setTestNow('2026-09-02 10:00:00');
        [$user] = $this->makeMedicationDay();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('まだ確認していない分があります');
    }

    public function test_secretary_suggests_goal_after_medication_is_complete(): void
    {
        Carbon::setTestNow('2026-09-02 10:00:00');
        [$user, $item] = $this->makeMedicationDay();

        foreach (['morning', 'evening'] as $timing) {
            CheckinEntry::create([
                'checkin_item_id' => $item->id,
                'user_id' => $user->id,
                'checked_on' => '2026-09-02',
                'timing' => $timing,
            ]);
        }

        LifeGoal::create([
            'user_id' => $user->id,
            'category' => 'exercise',
            'title' => '5分ストレッチ',
            'time_of_day' => 'anytime',
            'schedule_type' => 'daily',
            'decided_with' => 'self',
            'is_active' => true,
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('今日のお薬の確認ができましたね')
            ->assertSee('5分ストレッチ')
            ->assertSee('無理な日は休んで大丈夫');
    }

    public function test_secretary_mentions_todays_consultation(): void
    {
        Carbon::setTestNow('2026-09-02 10:00:00');
        $user = User::factory()->create();
        Treatment::create([
            'user_id' => $user->id,
            'name' => '乳腺外科の診察',
            'treatment_type' => 'consultation',
            'scheduled_on' => '2026-09-02',
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('今日は「乳腺外科の診察」の予定があります')
            ->assertSee('診察前のメモ');

        $event = UsageEvent::where('event_name', 'secretary.prompt_shown')->sole();
        $this->assertSame(['prompt_key' => 'appointment.today'], $event->context);
        $this->assertStringNotContainsString('乳腺外科', json_encode($event->context));
    }

    public function test_medication_reminder_takes_priority_over_appointment(): void
    {
        Carbon::setTestNow('2026-09-02 10:00:00');
        [$user] = $this->makeMedicationDay();
        Treatment::create([
            'user_id' => $user->id,
            'name' => '今日の診察',
            'treatment_type' => 'consultation',
            'scheduled_on' => '2026-09-02',
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('まだ確認していない分があります')
            ->assertDontSee('今日は「今日の診察」の予定があります');

        $event = UsageEvent::where('user_id', $user->id)
            ->where('event_name', 'secretary.prompt_shown')
            ->sole();
        $this->assertSame(['prompt_key' => 'medication.pending'], $event->context);
    }

    private function makeMedicationDay(): array
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->active()->create([
            'name' => '服薬管理',
            'uses_checkins' => true,
        ]);
        $item = CheckinItem::create([
            'project_id' => $project->id,
            'title' => '毎日のお薬',
            'kind' => 'medication',
            'medication_timings' => ['morning', 'evening'],
            'schedule_type' => 'daily',
            'is_active' => true,
        ]);

        return [$user, $item];
    }
}
