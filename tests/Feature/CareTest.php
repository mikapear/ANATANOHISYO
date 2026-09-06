<?php

namespace Tests\Feature;

use App\Models\CheckinItem;
use App\Models\Project;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('暮らしの予定');
    }
}