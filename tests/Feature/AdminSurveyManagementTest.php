<?php

namespace Tests\Feature;

use App\Models\SurveyAssignment;
use App\Models\SurveyDefinition;
use App\Models\SurveyQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSurveyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_cannot_manage_surveys(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.surveys.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_draft_and_add_question(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.surveys.store'), [
            'code' => 'self_management',
            'title' => 'セルフマネジメント確認',
            'description' => '生活の整え方を振り返ります。',
        ])->assertRedirect();

        $definition = SurveyDefinition::where('code', 'self_management')->firstOrFail();
        $this->assertFalse($definition->is_active);

        $this->actingAs($admin)->post(route('admin.survey-questions.store', $definition), [
            'key' => 'confidence',
            'prompt' => '自分で生活を整えられると思いますか',
            'response_type' => 'single_choice',
            'options_text' => "思わない\n少し思う\nとても思う",
            'is_required' => '1',
        ])->assertRedirect();

        $question = SurveyQuestion::firstOrFail();
        $this->assertSame(['1' => '思わない', '2' => '少し思う', '3' => 'とても思う'], $question->options);
        $this->assertTrue($question->is_required);
    }

    public function test_survey_needs_a_question_before_activation(): void
    {
        $admin = User::factory()->admin()->create();
        $definition = SurveyDefinition::create(['code' => 'empty', 'title' => '空のアンケート', 'version' => 1]);

        $this->actingAs($admin)->put(route('admin.surveys.update', $definition), [
            'code' => 'empty', 'title' => '空のアンケート', 'is_active' => '1',
        ])->assertSessionHasErrors('is_active');

        $this->assertFalse($definition->fresh()->is_active);
    }

    public function test_assigned_survey_questions_cannot_be_changed(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $definition = SurveyDefinition::create(['code' => 'locked', 'title' => '確定済み', 'version' => 1, 'is_active' => true]);
        $question = SurveyQuestion::create([
            'survey_definition_id' => $definition->id, 'key' => 'q1', 'prompt' => '変更前',
            'response_type' => 'text', 'is_required' => true, 'position' => 1,
        ]);
        SurveyAssignment::create([
            'user_id' => $user->id, 'survey_definition_id' => $definition->id,
            'phase' => 'baseline', 'due_on' => today(), 'available_from' => today(), 'status' => 'pending',
        ]);

        $this->actingAs($admin)->put(route('admin.survey-questions.update', $question), [
            'key' => 'q1', 'prompt' => '変更後', 'response_type' => 'text', 'is_required' => '1',
        ])->assertStatus(409);

        $this->assertSame('変更前', $question->fresh()->prompt);
    }

    public function test_admin_can_assign_three_phases_without_duplicates(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $secondUser = User::factory()->create();
        $definition = SurveyDefinition::create(['code' => 'active', 'title' => '有効', 'version' => 1, 'is_active' => true]);
        SurveyQuestion::create([
            'survey_definition_id' => $definition->id, 'key' => 'q1', 'prompt' => '質問',
            'response_type' => 'text', 'is_required' => true, 'position' => 1,
        ]);
        $payload = ['user_ids' => [$user->id, $secondUser->id], 'start_date' => '2026-09-09'];

        $this->actingAs($admin)->post(route('admin.survey-assignments.store', $definition), $payload)->assertRedirect();
        $this->actingAs($admin)->post(route('admin.survey-assignments.store', $definition), $payload)->assertRedirect();

        $this->assertDatabaseCount('survey_assignments', 6);
        $this->assertDatabaseHas('survey_assignments', ['user_id' => $user->id, 'phase' => 'baseline', 'due_on' => '2026-09-09']);
        $this->assertDatabaseHas('survey_assignments', ['user_id' => $user->id, 'phase' => 'week4', 'due_on' => '2026-10-07']);
        $this->assertDatabaseHas('survey_assignments', ['user_id' => $user->id, 'phase' => 'week8', 'due_on' => '2026-11-04']);
        $this->assertDatabaseHas('survey_assignments', ['user_id' => $secondUser->id, 'phase' => 'baseline', 'due_on' => '2026-09-09']);
    }

    public function test_draft_survey_and_admin_user_cannot_be_assigned(): void
    {
        $admin = User::factory()->admin()->create();
        $definition = SurveyDefinition::create(['code' => 'draft', 'title' => '下書き', 'version' => 1, 'is_active' => false]);
        SurveyQuestion::create([
            'survey_definition_id' => $definition->id, 'key' => 'q1', 'prompt' => '質問',
            'response_type' => 'text', 'is_required' => true, 'position' => 1,
        ]);

        $this->actingAs($admin)->post(route('admin.survey-assignments.store', $definition), [
            'user_ids' => [$admin->id], 'start_date' => '2026-09-09',
        ])->assertSessionHasErrors('survey');

        $definition->update(['is_active' => true]);
        $this->actingAs($admin)->post(route('admin.survey-assignments.store', $definition), [
            'user_ids' => [$admin->id], 'start_date' => '2026-09-09',
        ])->assertSessionHasErrors('user_ids.0');

        $this->assertDatabaseCount('survey_assignments', 0);
    }
}
