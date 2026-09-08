<?php

namespace Tests\Feature;

use App\Models\SurveyAnswer;
use App\Models\SurveyAssignment;
use App\Models\SurveyDefinition;
use App\Models\SurveyQuestion;
use App\Models\User;
use App\Services\SurveyScheduler;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SurveyFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_survey_is_assigned_at_baseline_four_and_eight_weeks(): void
    {
        $user = User::factory()->create();
        $definition = SurveyDefinition::create([
            'code' => 'self-management-v1',
            'title' => 'セルフマネジメント評価',
            'version' => 1,
            'is_active' => true,
        ]);

        $assignments = app(SurveyScheduler::class)->assign(
            $user,
            $definition,
            Carbon::parse('2026-09-09')
        );

        $this->assertSame(['baseline', 'week4', 'week8'], $assignments->pluck('phase')->all());
        $this->assertSame(
            ['2026-09-09', '2026-10-07', '2026-11-04'],
            $assignments->map(fn ($assignment) => $assignment->due_on->toDateString())->all()
        );
        $this->assertSame(['pending'], $assignments->pluck('status')->unique()->all());
    }

    public function test_assignment_is_idempotent_and_does_not_reset_progress(): void
    {
        $user = User::factory()->create();
        $definition = SurveyDefinition::create([
            'code' => 'evaluation-v1',
            'title' => '評価',
            'is_active' => true,
        ]);
        $scheduler = app(SurveyScheduler::class);
        $scheduler->assign($user, $definition, Carbon::parse('2026-09-09'));
        SurveyAssignment::where('phase', 'baseline')->update([
            'status' => 'in_progress',
            'started_at' => '2026-09-09 10:00:00',
        ]);

        $scheduler->assign($user, $definition, Carbon::parse('2026-09-10'));

        $this->assertDatabaseCount('survey_assignments', 3);
        $this->assertDatabaseHas('survey_assignments', [
            'user_id' => $user->id,
            'phase' => 'baseline',
            'due_on' => '2026-09-09',
            'status' => 'in_progress',
        ]);
    }

    public function test_inactive_draft_survey_cannot_be_assigned(): void
    {
        $definition = SurveyDefinition::create([
            'code' => 'draft-v1',
            'title' => '準備中',
            'is_active' => false,
        ]);

        $this->expectException(DomainException::class);

        app(SurveyScheduler::class)->assign(
            User::factory()->create(),
            $definition,
            Carbon::parse('2026-09-09')
        );
    }

    public function test_answer_is_structured_and_removed_with_its_assignment(): void
    {
        $user = User::factory()->create();
        $definition = SurveyDefinition::create([
            'code' => 'usability-v1',
            'title' => '使いやすさ',
            'is_active' => true,
        ]);
        $question = SurveyQuestion::create([
            'survey_definition_id' => $definition->id,
            'key' => 'easy_to_use',
            'prompt' => '使いやすかったですか',
            'response_type' => 'scale',
            'options' => ['min' => 1, 'max' => 5],
            'position' => 1,
        ]);
        $assignment = app(SurveyScheduler::class)
            ->assign($user, $definition, Carbon::parse('2026-09-09'))
            ->first();
        SurveyAnswer::create([
            'survey_assignment_id' => $assignment->id,
            'survey_question_id' => $question->id,
            'response' => ['value' => 4],
            'answered_at' => now(),
        ]);

        $this->assertSame(['value' => 4], SurveyAnswer::sole()->response);

        $assignment->delete();

        $this->assertDatabaseCount('survey_answers', 0);
    }

    public function test_deleting_user_removes_assignments_and_answers(): void
    {
        $user = User::factory()->create();
        $definition = SurveyDefinition::create([
            'code' => 'follow-up-v1',
            'title' => '継続評価',
            'is_active' => true,
        ]);
        $question = SurveyQuestion::create([
            'survey_definition_id' => $definition->id,
            'key' => 'continued',
            'prompt' => '続けられましたか',
            'response_type' => 'single_choice',
            'options' => ['yes', 'no'],
        ]);
        $assignment = app(SurveyScheduler::class)
            ->assign($user, $definition, Carbon::parse('2026-09-09'))
            ->first();
        SurveyAnswer::create([
            'survey_assignment_id' => $assignment->id,
            'survey_question_id' => $question->id,
            'response' => ['value' => 'yes'],
            'answered_at' => now(),
        ]);

        $user->delete();

        $this->assertDatabaseCount('survey_assignments', 0);
        $this->assertDatabaseCount('survey_answers', 0);
    }
}
