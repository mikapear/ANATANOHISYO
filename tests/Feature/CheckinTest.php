<?php

namespace Tests\Feature;

use App\Models\CheckinEntry;
use App\Models\CheckinItem;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CheckinTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_checkin_item_to_enabled_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create(['uses_checkins' => true]);

        $this->actingAs($user)
            ->post(route('checkin-items.store', $project), ['title' => '朝の薬'])
            ->assertRedirect();

        $this->assertDatabaseHas('checkin_items', [
            'project_id' => $project->id,
            'title' => '朝の薬',
            'is_active' => true,
        ]);
    }

    public function test_checkin_item_cannot_be_added_to_disabled_or_foreign_project(): void
    {
        $user = User::factory()->create();
        $disabled = Project::factory()->for($user)->create(['uses_checkins' => false]);
        $foreign = Project::factory()->for(User::factory())->create(['uses_checkins' => true]);

        $this->actingAs($user)->post(route('checkin-items.store', $disabled), ['title' => '項目'])->assertForbidden();
        $this->post(route('checkin-items.store', $foreign), ['title' => '項目'])->assertForbidden();
    }

    public function test_user_can_add_note_and_remove_daily_flower(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create(['uses_checkins' => true]);
        $item = CheckinItem::create(['project_id' => $project->id, 'title' => 'ストレッチ']);

        $this->actingAs($user)->put(route('checkin-entries.update', $item), [
            'checked_on' => '2026-08-25',
            'checked' => '1',
            'note' => '朝に10分',
        ])->assertRedirect();

        $this->assertDatabaseHas('checkin_entries', [
            'checkin_item_id' => $item->id,
            'user_id' => $user->id,
            'checked_on' => '2026-08-25',
            'note' => '朝に10分',
        ]);

        $this->put(route('checkin-entries.update', $item), [
            'checked_on' => '2026-08-25',
            'checked' => '0',
        ])->assertRedirect();

        $this->assertDatabaseMissing('checkin_entries', [
            'checkin_item_id' => $item->id,
            'checked_on' => '2026-08-25',
        ]);
    }

    public function test_user_cannot_change_foreign_checkin(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for(User::factory())->create(['uses_checkins' => true]);
        $item = CheckinItem::create(['project_id' => $project->id, 'title' => '夜の薬']);

        $this->actingAs($user)->put(route('checkin-entries.update', $item), [
            'checked_on' => '2026-08-25',
            'checked' => '1',
        ])->assertForbidden();

        $this->assertDatabaseCount('checkin_entries', 0);
    }

    public function test_today_and_calendar_show_daily_checkins(): void
    {
        Carbon::setTestNow('2026-08-25 09:00:00');
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->active()->create([
            'name' => '服薬管理',
            'uses_checkins' => true,
            'uses_calendar' => true,
        ]);
        $item = CheckinItem::create(['project_id' => $project->id, 'title' => '朝の薬']);
        CheckinEntry::create([
            'checkin_item_id' => $item->id,
            'user_id' => $user->id,
            'checked_on' => '2026-08-25',
            'note' => '服用済み',
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('お薬・習慣の確認')
            ->assertSee('朝の薬');

        $this->get(route('calendar.index', [
            'month' => '2026-08',
            'date' => '2026-08-25',
            'project_id' => $project->id,
        ]))
            ->assertOk()
            ->assertSee('朝の薬')
            ->assertSee('服用済み');

        Carbon::setTestNow();
    }

    public function test_user_can_register_medication_with_multiple_timings(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create(['uses_checkins' => true]);

        $this->actingAs($user)->post(route('checkin-items.store', $project), [
            'title' => 'ホルモン剤',
            'kind' => 'medication',
            'medication_timings' => ['morning', 'evening'],
        ])->assertRedirect();

        $item = CheckinItem::where('project_id', $project->id)->firstOrFail();
        $this->assertSame('medication', $item->kind);
        $this->assertSame(['morning', 'evening'], $item->medication_timings);
    }

    public function test_medication_requires_at_least_one_timing(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create(['uses_checkins' => true]);

        $this->actingAs($user)->post(route('checkin-items.store', $project), [
            'title' => '朝のお薬',
            'kind' => 'medication',
        ])->assertSessionHasErrors('medication_timings');

        $this->assertDatabaseCount('checkin_items', 0);
    }

    public function test_medication_can_be_checked_for_each_timing(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create(['uses_checkins' => true]);
        $item = CheckinItem::create([
            'project_id' => $project->id,
            'title' => '内服薬',
            'kind' => 'medication',
            'medication_timings' => ['morning', 'evening'],
        ]);

        $this->actingAs($user)->put(route('checkin-entries.update', $item), [
            'checked_on' => '2026-08-25',
            'timing' => 'morning',
            'checked' => '1',
        ])->assertRedirect();

        $this->put(route('checkin-entries.update', $item), [
            'checked_on' => '2026-08-25',
            'timing' => 'evening',
            'checked' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('checkin_entries', ['checkin_item_id' => $item->id, 'timing' => 'morning']);
        $this->assertDatabaseHas('checkin_entries', ['checkin_item_id' => $item->id, 'timing' => 'evening']);
        $this->assertDatabaseCount('checkin_entries', 2);
    }

    public function test_medication_can_use_repeating_treatment_and_rest_cycle(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->active()->create(['uses_checkins' => true]);

        $this->actingAs($user)->post(route('checkin-items.store', $project), [
            'title' => '周期内服薬',
            'kind' => 'medication',
            'medication_timings' => ['morning'],
            'schedule_type' => 'cycle',
            'starts_on' => '2026-09-01',
            'cycle_on_days' => 14,
            'cycle_rest_days' => 7,
        ])->assertRedirect();

        $item = CheckinItem::where('project_id', $project->id)->firstOrFail();
        $this->assertSame('cycle', $item->schedule_type);
        $this->assertSame(14, $item->cycle_on_days);
        $this->assertSame(7, $item->cycle_rest_days);
        $this->assertTrue($item->isScheduledFor(Carbon::parse('2026-09-01')));
        $this->assertTrue($item->isScheduledFor(Carbon::parse('2026-09-14')));
        $this->assertFalse($item->isScheduledFor(Carbon::parse('2026-09-15')));
        $this->assertFalse($item->isScheduledFor(Carbon::parse('2026-09-21')));
        $this->assertTrue($item->isScheduledFor(Carbon::parse('2026-09-22')));
    }

    public function test_medication_cannot_be_marked_during_rest_period(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->active()->create(['uses_checkins' => true]);
        $item = CheckinItem::create([
            'project_id' => $project->id,
            'title' => '休薬日の薬',
            'kind' => 'medication',
            'medication_timings' => ['morning'],
            'schedule_type' => 'cycle',
            'starts_on' => '2026-09-01',
            'cycle_on_days' => 14,
            'cycle_rest_days' => 7,
        ]);

        Carbon::setTestNow('2026-09-15 09:00:00');

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertDontSee('休薬日の薬');
        $this->put(route('checkin-entries.update', $item), [
            'checked_on' => '2026-09-15',
            'timing' => 'morning',
            'checked' => '1',
        ])->assertSessionHasErrors('checked_on');

        $this->assertDatabaseCount('checkin_entries', 0);
        Carbon::setTestNow();
    }

    public function test_project_page_is_for_configuration_not_completion(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create(['uses_checkins' => true]);
        $item = CheckinItem::create(['project_id' => $project->id, 'title' => '体調確認']);

        $this->actingAs($user)->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('習慣・服薬を登録する')
            ->assertDontSee(route('checkin-entries.update', $item), false);
    }

    public function test_weekday_schedule_only_appears_and_accepts_marks_on_scheduled_day(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->active()->create(['uses_checkins' => true]);
        $item = CheckinItem::create([
            'project_id' => $project->id,
            'title' => '火曜日の運動',
            'schedule_type' => 'weekdays',
            'weekdays' => [2],
        ]);

        Carbon::setTestNow('2026-08-25 09:00:00');
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('火曜日の運動');

        Carbon::setTestNow('2026-08-26 09:00:00');
        $this->get(route('dashboard'))->assertOk()->assertDontSee('火曜日の運動');
        $this->put(route('checkin-entries.update', $item), [
            'checked_on' => '2026-08-26',
            'timing' => 'once',
            'checked' => '1',
        ])->assertSessionHasErrors('checked_on');

        $this->assertDatabaseCount('checkin_entries', 0);
        Carbon::setTestNow();
    }
}
