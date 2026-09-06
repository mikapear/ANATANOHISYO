<?php

namespace Tests\Feature;

use App\Models\LifeGoal;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LifeGoalTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_life_goals(): void
    {
        $this->get('/goals')->assertRedirect(route('login'));
    }

    public function test_goal_page_starts_with_three_clear_entrances(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('goals.index'))
            ->assertOk()
            ->assertSee('無理なく続けたいことを、自分のペースで決めます。')
            ->assertSee('からだを動かす')
            ->assertSee('食事・水分')
            ->assertSee('くらしを整える')
            ->assertSee('登録・変更');
    }

    public function test_each_goal_category_has_its_own_management_page(): void
    {
        $user = User::factory()->create();
        LifeGoal::create([
            'user_id' => $user->id,
            'category' => 'exercise',
            'title' => '10分歩く',
            'time_of_day' => 'anytime',
            'schedule_type' => 'daily',
            'decided_with' => 'self',
        ]);
        LifeGoal::create([
            'user_id' => $user->id,
            'category' => 'nutrition',
            'title' => '水分をとる',
            'time_of_day' => 'anytime',
            'schedule_type' => 'daily',
            'decided_with' => 'self',
        ]);

        $this->actingAs($user)->get(route('goals.manage', 'exercise'))
            ->assertOk()
            ->assertSee('からだを動かす目標を作る')
            ->assertSee('10分歩く')
            ->assertDontSee('水分をとる')
            ->assertDontSee('関連する暮らしの予定');
    }

    public function test_user_can_create_a_self_defined_life_goal(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->active()->create();

        $this->actingAs($user)->post(route('goals.store'), [
            'category' => 'exercise',
            'title' => '10分歩く',
            'target_amount' => 10,
            'target_unit' => '分',
            'time_of_day' => 'morning',
            'schedule_type' => 'weekdays',
            'weekdays' => [1, 3, 5],
            'starts_on' => '2026-09-02',
            'decided_with' => 'self',
            'project_id' => $project->id,
            'note' => 'つらい日は休む',
        ])->assertRedirect();

        $goal = LifeGoal::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('exercise', $goal->category);
        $this->assertSame('10分歩く', $goal->title);
        $this->assertSame([1, 3, 5], $goal->weekdays);
        $this->assertSame('self', $goal->decided_with);
        $this->assertTrue($goal->isScheduledFor(Carbon::parse('2026-09-02')));
        $this->assertFalse($goal->isScheduledFor(Carbon::parse('2026-09-03')));

        $this->get(route('goals.index'))->assertOk()->assertSee('10分歩く');
        $this->get(route('goals.manage', 'exercise'))->assertOk()->assertSee('自分で決めた');
    }

    public function test_foreign_project_is_rejected_for_goal(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for(User::factory())->create();

        $this->actingAs($user)->post(route('goals.store'), [
            'category' => 'nutrition',
            'title' => '水分をとる',
            'time_of_day' => 'anytime',
            'schedule_type' => 'daily',
            'decided_with' => 'self',
            'project_id' => $project->id,
        ])->assertSessionHasErrors('project_id');

        $this->assertDatabaseCount('life_goals', 0);
    }

    public function test_user_cannot_change_another_users_goal(): void
    {
        $goal = LifeGoal::create([
            'user_id' => User::factory()->create()->id,
            'category' => 'other',
            'title' => '他人の目標',
            'time_of_day' => 'anytime',
            'schedule_type' => 'daily',
            'decided_with' => 'self',
        ]);

        $user = User::factory()->create();
        $payload = [
            'category' => 'other',
            'title' => '変更',
            'time_of_day' => 'anytime',
            'schedule_type' => 'daily',
            'decided_with' => 'self',
        ];

        $this->actingAs($user)->patch(route('goals.update', $goal), $payload)->assertForbidden();
        $this->delete(route('goals.destroy', $goal))->assertForbidden();
    }
}
