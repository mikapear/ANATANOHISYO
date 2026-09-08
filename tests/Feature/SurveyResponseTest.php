<?php

namespace Tests\Feature;

use App\Models\SurveyAssignment;
use App\Models\SurveyDefinition;
use App\Models\SurveyQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SurveyResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_today_shows_available_survey_for_its_user(): void
    {
        Carbon::setTestNow('2026-09-09 12:00:00');
        [$user, $assignment] = $this->survey();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('確認アンケート')
            ->assertSee(route('surveys.edit', $assignment));
    }

    public function test_user_cannot_open_another_users_or_future_survey(): void
    {
        Carbon::setTestNow('2026-09-09 12:00:00');
        [$user, $assignment] = $this->survey();

        $this->actingAs(User::factory()->create())->get(route('surveys.edit', $assignment))->assertForbidden();

        $assignment->update(['available_from' => now()->addDay()->toDateString()]);
        $this->actingAs($user)->get(route('surveys.edit', $assignment))->assertNotFound();
    }

    public function test_user_can_save_partial_answers_and_finish_later(): void
    {
        Carbon::setTestNow('2026-09-09 12:00:00');
        [$user, $assignment, $question] = $this->survey();

        $this->actingAs($user)->put(route('surveys.update', $assignment), [
            'action' => 'save',
            'answers' => [$question->id => 'good'],
        ])->assertRedirect();

        $this->assertDatabaseHas('survey_assignments', ['id' => $assignment->id, 'status' => 'in_progress']);
        $this->assertDatabaseHas('survey_answers', ['survey_assignment_id' => $assignment->id, 'survey_question_id' => $question->id]);
        $this->assertDatabaseHas('usage_events', ['user_id' => $user->id, 'event_name' => 'survey.saved']);

        $this->actingAs($user)->put(route('surveys.update', $assignment), [
            'action' => 'complete',
            'answers' => [$question->id => 'usual'],
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('survey_assignments', ['id' => $assignment->id, 'status' => 'completed']);
        $this->assertDatabaseHas('usage_events', ['user_id' => $user->id, 'event_name' => 'survey.completed']);
    }

    public function test_required_and_unknown_answers_are_rejected_on_completion(): void
    {
        Carbon::setTestNow('2026-09-09 12:00:00');
        [$user, $assignment, $question] = $this->survey();

        $this->actingAs($user)->put(route('surveys.update', $assignment), ['action' => 'complete'])
            ->assertSessionHasErrors('answer');

        $this->actingAs($user)->put(route('surveys.update', $assignment), [
            'action' => 'complete',
            'answers' => [$question->id => 'unknown'],
        ])->assertSessionHasErrors('answer');

        $this->assertDatabaseMissing('survey_assignments', ['id' => $assignment->id, 'status' => 'completed']);
    }

    private function survey(): array
    {
        $user = User::factory()->create();
        $definition = SurveyDefinition::create([
            'code' => 'check', 'title' => '確認アンケート', 'version' => 1, 'is_active' => true,
        ]);
        $question = SurveyQuestion::create([
            'survey_definition_id' => $definition->id,
            'key' => 'condition',
            'prompt' => '今日の調子はいかがですか',
            'response_type' => 'single_choice',
            'options' => ['good' => 'よい', 'usual' => 'ふつう'],
            'is_required' => true,
            'position' => 1,
        ]);
        $assignment = SurveyAssignment::create([
            'user_id' => $user->id,
            'survey_definition_id' => $definition->id,
            'phase' => 'baseline',
            'due_on' => now()->toDateString(),
            'available_from' => now()->toDateString(),
            'status' => 'pending',
        ]);

        return [$user, $assignment, $question];
    }
}
