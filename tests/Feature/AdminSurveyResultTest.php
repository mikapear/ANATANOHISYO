<?php

namespace Tests\Feature;

use App\Models\SurveyAnswer;
use App\Models\SurveyAssignment;
use App\Models\SurveyDefinition;
use App\Models\SurveyQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSurveyResultTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_receive_distinct_research_codes(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();

        $this->assertNotNull($first->research_code);
        $this->assertNotSame($first->research_code, $second->research_code);
        $this->assertSame(16, strlen($first->research_code));
    }

    public function test_regular_user_cannot_view_or_export_results(): void
    {
        [$definition, $assignment] = $this->answeredSurvey();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.survey-results.show', $assignment))->assertForbidden();
        $this->actingAs($user)->get(route('admin.surveys.export', $definition))->assertForbidden();
    }

    public function test_admin_can_view_answer_details(): void
    {
        [$definition, $assignment, $participant] = $this->answeredSurvey();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.survey-results.show', $assignment))
            ->assertOk()
            ->assertSee($definition->title)
            ->assertSee($participant->research_code)
            ->assertSee('=個人情報らしき自由記述');
    }

    public function test_csv_excludes_identity_and_escapes_spreadsheet_formula(): void
    {
        [$definition, $assignment, $participant] = $this->answeredSurvey();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.surveys.export', $definition))
            ->assertOk()
            ->assertDownload();
        $csv = $response->streamedContent();

        $this->assertStringContainsString('research_id,survey_code,survey_version,phase', $csv);
        $this->assertStringContainsString($participant->research_code, $csv);
        $this->assertStringContainsString("'=個人情報らしき自由記述", $csv);
        $this->assertStringNotContainsString($participant->name, $csv);
        $this->assertStringNotContainsString($participant->email, $csv);
    }

    private function answeredSurvey(): array
    {
        $participant = User::factory()->create(['name' => '参加者氏名', 'email' => 'participant@example.com']);
        $definition = SurveyDefinition::create(['code' => 'result_test', 'title' => '結果確認', 'version' => 1, 'is_active' => true]);
        $question = SurveyQuestion::create([
            'survey_definition_id' => $definition->id, 'key' => 'free_text', 'prompt' => '自由記述',
            'response_type' => 'textarea', 'is_required' => true, 'position' => 1,
        ]);
        $assignment = SurveyAssignment::create([
            'user_id' => $participant->id, 'survey_definition_id' => $definition->id,
            'phase' => 'baseline', 'due_on' => today(), 'available_from' => today(),
            'status' => 'completed', 'started_at' => now(), 'completed_at' => now(),
        ]);
        SurveyAnswer::create([
            'survey_assignment_id' => $assignment->id,
            'survey_question_id' => $question->id,
            'response' => ['value' => '=個人情報らしき自由記述'],
            'answered_at' => now(),
        ]);

        return [$definition, $assignment, $participant];
    }
}
