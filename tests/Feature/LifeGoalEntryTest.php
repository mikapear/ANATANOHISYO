<?php

namespace Tests\Feature;

use App\Models\LifeGoal;
use App\Models\LifeGoalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LifeGoalEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_record_and_change_todays_goal_result(): void
    {
        Carbon::setTestNow('2026-09-02 09:00:00');
        $user = User::factory()->create();
        $goal = LifeGoal::create([
            'user_id' => $user->id,
            'category' => 'exercise',
            'title' => '10分歩く',
            'time_of_day' => 'morning',
            'schedule_type' => 'daily',
            'decided_with' => 'self',
            'is_active' => true,
        ]);

        $this->actingAs($user)->put(route('goal-entries.update', $goal), [
            'recorded_on' => '2026-09-02',
            'status' => 'partial',
            'actual_amount' => 5,
            'note' => 'ゆっくり歩けた',
        ])->assertRedirect();

        $this->assertDatabaseHas('life_goal_entries', [
            'life_goal_id' => $goal->id,
            'status' => 'partial',
            'actual_amount' => 5,
            'note' => 'ゆっくり歩けた',
        ]);

        $this->put(route('goal-entries.update', $goal), [
            'recorded_on' => '2026-09-02',
            'status' => 'rest',
        ])->assertRedirect();

        $this->assertDatabaseCount('life_goal_entries', 1);
        $this->assertSame('rest', LifeGoalEntry::firstOrFail()->status);
    }

    public function test_user_can_record_food_or_hydration_details(): void
    {
        Carbon::setTestNow('2026-09-02 12:00:00');
        $user = User::factory()->create();
        $goal = LifeGoal::create([
            'user_id' => $user->id,
            'category' => 'nutrition',
            'title' => '水分をとる',
            'target_amount' => 1200,
            'target_unit' => 'ml',
            'time_of_day' => 'anytime',
            'schedule_type' => 'daily',
            'decided_with' => 'self',
            'is_active' => true,
        ]);

        $this->actingAs($user)->put(route('goal-entries.update', $goal), [
            'recorded_on' => '2026-09-02',
            'status' => 'partial',
            'nutrition_type' => 'hydration',
            'food_details' => '水と麦茶',
            'actual_amount' => 800,
        ])->assertRedirect();

        $this->assertDatabaseHas('life_goal_entries', [
            'life_goal_id' => $goal->id,
            'status' => 'partial',
            'nutrition_type' => 'hydration',
            'food_details' => '水と麦茶',
            'actual_amount' => 800,
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('食事・水分の区分')
            ->assertSee('食べたもの・飲んだもの')
            ->assertSee('水と麦茶');
    }

    public function test_invalid_nutrition_type_is_rejected(): void
    {
        $user = User::factory()->create();
        $goal = LifeGoal::create([
            'user_id' => $user->id,
            'category' => 'nutrition',
            'title' => '食事を残す',
            'time_of_day' => 'anytime',
            'schedule_type' => 'daily',
            'decided_with' => 'self',
            'is_active' => true,
        ]);

        $this->actingAs($user)->put(route('goal-entries.update', $goal), [
            'recorded_on' => now()->toDateString(),
            'status' => 'completed',
            'nutrition_type' => 'invalid',
        ])->assertSessionHasErrors('nutrition_type');

        $this->assertDatabaseCount('life_goal_entries', 0);
    }
    public function test_user_cannot_record_another_users_goal(): void
    {
        $owner = User::factory()->create();
        $goal = LifeGoal::create([
            'user_id' => $owner->id,
            'category' => 'nutrition',
            'title' => '水分をとる',
            'time_of_day' => 'anytime',
            'schedule_type' => 'daily',
            'decided_with' => 'self',
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create())->put(route('goal-entries.update', $goal), [
            'recorded_on' => '2026-09-02',
            'status' => 'completed',
        ])->assertForbidden();

        $this->assertDatabaseCount('life_goal_entries', 0);
    }

    public function test_goal_cannot_be_recorded_on_an_unscheduled_day(): void
    {
        $user = User::factory()->create();
        $goal = LifeGoal::create([
            'user_id' => $user->id,
            'category' => 'other',
            'title' => '平日の目標',
            'time_of_day' => 'anytime',
            'schedule_type' => 'weekdays',
            'weekdays' => [1],
            'decided_with' => 'self',
            'is_active' => true,
        ]);

        $this->actingAs($user)->put(route('goal-entries.update', $goal), [
            'recorded_on' => '2026-09-02',
            'status' => 'completed',
        ])->assertSessionHasErrors('goal');

        $this->assertDatabaseCount('life_goal_entries', 0);
    }

    public function test_todays_scheduled_goal_is_visible_on_dashboard(): void
    {
        Carbon::setTestNow('2026-09-02 09:00:00');
        $user = User::factory()->create();
        LifeGoal::create([
            'user_id' => $user->id,
            'category' => 'exercise',
            'title' => '朝にストレッチ',
            'time_of_day' => 'morning',
            'schedule_type' => 'daily',
            'decided_with' => 'self',
            'is_active' => true,
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('今日の目標')
            ->assertSee('朝にストレッチ')
            ->assertSee('実際にできた量')
            ->assertSee('ひとことメモ')
            ->assertSee('少しできた')
            ->assertSee('今日は休む');
    }
}