<?php

namespace Tests\Feature;

use App\Models\CheckinItem;
use App\Models\Project;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CareTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_care_page(): void
    {
        $this->get('/care')->assertRedirect(route('login'));
    }

    public function test_care_page_shows_own_medication_and_treatment_only(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create([
            'name' => 'おくすり管理',
            'uses_checkins' => true,
        ]);
        CheckinItem::create([
            'project_id' => $project->id,
            'title' => '朝のお薬',
            'kind' => 'medication',
            'medication_timings' => ['morning'],
            'schedule_type' => 'daily',
            'is_active' => true,
        ]);
        Treatment::create([
            'user_id' => $user->id,
            'name' => '本人の治療',
            'treatment_type' => 'chemotherapy',
            'scheduled_on' => today()->addDay(),
        ]);
        Treatment::create([
            'user_id' => User::factory()->create()->id,
            'name' => '他人の治療',
            'treatment_type' => 'infusion',
            'scheduled_on' => today()->addDay(),
        ]);

        $this->actingAs($user)->get(route('care.index'))
            ->assertOk()
            ->assertSee('おくすり・治療')
            ->assertSee('朝のお薬')
            ->assertSee('本人の治療')
            ->assertDontSee('他人の治療')
            ->assertSee('やること');
    }

    public function test_user_can_register_medication_without_creating_project_manually(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('medications.create'))
            ->assertOk()
            ->assertSee('お薬を登録する')
            ->assertSee('medication-unit-options')
            ->assertSee('カプセル')
            ->assertSee('μg')
            ->assertDontSee('暮らしの予定名');

        $this->actingAs($user)->post(route('medications.store'), [
            'title' => '朝の薬',
            'medication_timings' => ['morning', 'evening'],
            'dose_amount' => 1,
            'dose_unit' => '錠',
            'schedule_type' => 'daily',
        ])->assertRedirect(route('care.index'));

        $project = Project::where('user_id', $user->id)->sole();

        $this->assertSame('おくすり', $project->name);
        $this->assertTrue($project->uses_checkins);
        $this->assertDatabaseHas('checkin_items', [
            'project_id' => $project->id,
            'title' => '朝の薬',
            'kind' => 'medication',
            'schedule_type' => 'daily',
        ]);
    }

    public function test_registering_another_medication_reuses_existing_medication_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create([
            'name' => '治療のお薬',
            'template' => 'checkin',
            'uses_checkins' => true,
            'status' => 'active',
        ]);
        CheckinItem::create([
            'project_id' => $project->id,
            'title' => '既存薬',
            'kind' => 'medication',
            'medication_timings' => ['morning'],
            'schedule_type' => 'daily',
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('medications.store'), [
            'title' => '追加薬',
            'medication_timings' => ['bedtime'],
            'schedule_type' => 'daily',
        ])->assertRedirect(route('care.index'));

        $this->assertSame(1, Project::where('user_id', $user->id)->count());
        $this->assertDatabaseHas('checkin_items', [
            'project_id' => $project->id,
            'title' => '追加薬',
            'kind' => 'medication',
        ]);
    }

    public function test_user_can_register_and_record_as_needed_medication_more_than_once(): void
    {
        Carbon::setTestNow('2026-09-10 14:30:00');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('medications.store'), [
            'title' => '下痢止め',
            'medication_type' => 'as_needed',
            'dose_amount' => 1,
            'dose_unit' => '錠',
            'schedule_type' => 'daily',
        ])->assertRedirect(route('care.index'));

        $medication = CheckinItem::where('title', '下痢止め')->sole();
        $this->assertTrue($medication->is_as_needed);
        $this->assertSame([], $medication->medication_timings);

        $this->post(route('as-needed-medications.store', $medication))->assertRedirect();
        Carbon::setTestNow('2026-09-10 18:15:00');
        $this->post(route('as-needed-medications.store', $medication))->assertRedirect();

        $this->assertDatabaseCount('as_needed_medication_usages', 2);
        $this->get(route('care.index'))->assertOk()->assertSee('今日 14:30')->assertSee('今日 18:15');
        $this->get(route('calendar.index', ['month' => '2026-09', 'date' => '2026-09-10']))
            ->assertOk()->assertSee('頓服 2回')->assertSee('下痢止め・14:30')->assertSee('下痢止め・18:15');

        Carbon::setTestNow();
    }
}
