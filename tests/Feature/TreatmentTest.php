<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

        $this->actingAs($user)->post(route('treatments.store'), [
            'name' => 'AC療法',
            'treatment_type' => 'chemotherapy',
            'scheduled_on' => '2026-09-10',
            'scheduled_at' => '10:30',
            'cycle_number' => 3,
            'hospital' => 'テスト病院',
            'department' => '乳腺外科',
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
            ->assertSee('診察・治療 1')
            ->assertSee('本人の抗がん剤')
            ->assertSee('第2クール')
            ->assertDontSee('他人の治療');

        $this->get('/treatments?scheduled_on=2026-09-10')
            ->assertOk()
            ->assertSee('value="2026-09-10"', false);
    }

    public function test_user_can_register_a_consultation_and_save_visit_summary(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('treatments.store'), [
            'name' => '乳腺外科の診察',
            'treatment_type' => 'consultation',
            'scheduled_on' => '2026-09-10',
            'scheduled_at' => '10:30',
            'hospital' => 'テスト病院',
            'department' => '乳腺外科',
            'note' => '痛みについて相談する',
        ])->assertRedirect();

        $treatment = Treatment::sole();
        $this->patch(route('treatments.summary', $treatment), [
            'visit_summary' => '検査結果の説明を受けた。薬は変更なし。',
        ])->assertRedirect();

        $this->assertDatabaseHas('treatments', [
            'id' => $treatment->id,
            'treatment_type' => 'consultation',
            'note' => '痛みについて相談する',
            'visit_summary' => '検査結果の説明を受けた。薬は変更なし。',
        ]);
        $this->get(route('treatments.index'))
            ->assertOk()
            ->assertSee('相談メモ')
            ->assertSee('検査結果の説明を受けた。薬は変更なし。');
    }

    public function test_user_cannot_update_another_users_visit_summary(): void
    {
        $treatment = Treatment::create([
            'user_id' => User::factory()->create()->id,
            'name' => '他人の診察',
            'treatment_type' => 'consultation',
            'scheduled_on' => '2026-09-10',
        ]);

        $this->actingAs(User::factory()->create())
            ->patch(route('treatments.summary', $treatment), ['visit_summary' => '変更'])
            ->assertForbidden();

        $this->assertNull($treatment->fresh()->visit_summary);
    }

    public function test_today_shows_only_todays_own_consultation(): void
    {
        Carbon::setTestNow('2026-09-10 08:00:00');
        $user = User::factory()->create();
        Treatment::create([
            'user_id' => $user->id,
            'name' => '今日の診察',
            'treatment_type' => 'consultation',
            'scheduled_on' => '2026-09-10',
            'note' => '先生に聞きたいこと',
        ]);
        Treatment::create([
            'user_id' => $user->id,
            'name' => '明日の診察',
            'treatment_type' => 'consultation',
            'scheduled_on' => '2026-09-11',
        ]);
        Treatment::create([
            'user_id' => User::factory()->create()->id,
            'name' => '他人の診察',
            'treatment_type' => 'consultation',
            'scheduled_on' => '2026-09-10',
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('今日の診察')
            ->assertSee('先生に聞きたいこと')
            ->assertSee('診察後のメモを書く')
            ->assertDontSee('明日の診察')
            ->assertDontSee('他人の診察');

        Carbon::setTestNow();
    }
}
