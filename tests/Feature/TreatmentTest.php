<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TreatmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_treatments(): void
    {
        $this->get('/treatments')->assertRedirect(route('login'));
    }

    public function test_user_can_register_and_view_treatment_schedule(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->active()->create(['name' => '治療日誌']);

        $this->actingAs($user)->post(route('treatments.store'), [
            'name' => 'AC療法',
            'treatment_type' => 'chemotherapy',
            'scheduled_on' => '2026-09-10',
            'scheduled_at' => '10:30',
            'cycle_number' => 3,
            'hospital' => 'テスト病院',
            'department' => '乳腺外科',
            'project_id' => $project->id,
            'note' => '採血後に診察',
        ])->assertRedirect();

        $this->assertDatabaseHas('treatments', [
            'user_id' => $user->id,
            'name' => 'AC療法',
            'treatment_type' => 'chemotherapy',
            'scheduled_on' => '2026-09-10',
            'cycle_number' => 3,
            'status' => 'scheduled',
        ]);

        $this->get(route('treatments.index'))
            ->assertOk()
            ->assertSee('AC療法')
            ->assertSee('第3クール')
            ->assertSee('テスト病院')
            ->assertSee('採血後に診察');
    }

    public function test_foreign_project_is_rejected_for_treatment(): void
    {
        $user = User::factory()->create();
        $foreignProject = Project::factory()->for(User::factory())->create();

        $this->actingAs($user)->post(route('treatments.store'), [
            'name' => '点滴',
            'treatment_type' => 'infusion',
            'scheduled_on' => '2026-09-10',
            'project_id' => $foreignProject->id,
        ])->assertSessionHasErrors('project_id');

        $this->assertDatabaseCount('treatments', 0);
    }

    public function test_user_cannot_change_another_users_treatment(): void
    {
        $owner = User::factory()->create();
        $treatment = Treatment::create([
            'user_id' => $owner->id,
            'name' => '他人の治療',
            'treatment_type' => 'injection',
            'scheduled_on' => '2026-09-10',
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('treatments.update', $treatment), [
            'name' => '変更',
            'treatment_type' => 'other',
            'scheduled_on' => '2026-09-11',
        ])->assertForbidden();
        $this->delete(route('treatments.destroy', $treatment))->assertForbidden();
    }

    public function test_user_can_update_treatment_status(): void
    {
        $user = User::factory()->create();
        $treatment = Treatment::create([
            'user_id' => $user->id,
            'name' => '抗がん剤治療',
            'treatment_type' => 'chemotherapy',
            'scheduled_on' => '2026-09-10',
            'status' => 'scheduled',
        ]);

        $this->actingAs($user)->patch(route('treatments.status', $treatment), [
            'status' => 'completed',
        ])->assertRedirect();

        $this->assertDatabaseHas('treatments', ['id' => $treatment->id, 'status' => 'completed']);
        $this->get(route('treatments.index'))->assertOk()->assertSee('実施');
    }

    public function test_user_cannot_update_another_users_treatment_status(): void
    {
        $treatment = Treatment::create([
            'user_id' => User::factory()->create()->id,
            'name' => '他人の治療',
            'treatment_type' => 'infusion',
            'scheduled_on' => '2026-09-10',
        ]);

        $this->actingAs(User::factory()->create())
            ->patch(route('treatments.status', $treatment), ['status' => 'cancelled'])
            ->assertForbidden();
    }

    public function test_calendar_shows_only_own_treatment_and_prefills_date(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Treatment::create([
            'user_id' => $user->id,
            'name' => '本人の抗がん剤',
            'treatment_type' => 'chemotherapy',
            'scheduled_on' => '2026-09-10',
            'cycle_number' => 2,
        ]);
        Treatment::create([
            'user_id' => $other->id,
            'name' => '他人の治療',
            'treatment_type' => 'infusion',
            'scheduled_on' => '2026-09-10',
        ]);

        $this->actingAs($user)->get('/calendar?month=2026-09&date=2026-09-10')
            ->assertOk()
            ->assertSee('治療 1')
            ->assertSee('本人の抗がん剤')
            ->assertSee('第2クール')
            ->assertDontSee('他人の治療');

        $this->get('/treatments?scheduled_on=2026-09-10')
            ->assertOk()
            ->assertSee('value="2026-09-10"', false);
    }
}