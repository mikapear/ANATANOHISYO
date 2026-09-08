<?php

namespace Tests\Feature;

use App\Models\CheckinEntry;
use App\Models\CheckinItem;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MedicationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_medication_details(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create(['uses_checkins' => true]);

        $this->actingAs($user)->post(route('checkin-items.store', $project), [
            'title' => 'ホルモン剤',
            'kind' => 'medication',
            'medication_timings' => ['morning'],
            'dose_amount' => '1.5',
            'dose_unit' => '錠',
            'medication_instructions' => '朝食後に水で服用',
            'medication_precautions' => '処方内容が変わったら更新する',
            'schedule_type' => 'daily',
        ])->assertRedirect();

        $this->assertDatabaseHas('checkin_items', [
            'project_id' => $project->id,
            'dose_amount' => 1.5,
            'dose_unit' => '錠',
            'medication_instructions' => '朝食後に水で服用',
            'medication_precautions' => '処方内容が変わったら更新する',
        ]);
        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('1.5錠')
            ->assertSee('朝食後に水で服用');
    }

    public function test_user_can_record_and_change_medication_status(): void
    {
        Carbon::setTestNow('2026-09-07 08:00:00');
        [$user, $item] = $this->medication();

        $this->actingAs($user)->put(route('checkin-entries.update', $item), [
            'checked_on' => '2026-09-07',
            'timing' => 'morning',
            'checked' => '1',
            'status' => 'missed',
        ])->assertRedirect();

        $entry = CheckinEntry::sole();
        $this->assertSame('missed', $entry->status);
        $this->assertSame('2026-09-07 08:00:00', $entry->confirmed_at->format('Y-m-d H:i:s'));

        Carbon::setTestNow('2026-09-07 09:15:00');
        $this->put(route('checkin-entries.update', $item), [
            'checked_on' => '2026-09-07',
            'timing' => 'morning',
            'checked' => '1',
            'status' => 'taken',
        ])->assertRedirect();

        $this->assertDatabaseCount('checkin_entries', 1);
        $entry->refresh();
        $this->assertSame('taken', $entry->status);
        $this->assertSame('2026-09-07 09:15:00', $entry->confirmed_at->format('Y-m-d H:i:s'));
    }

    public function test_invalid_medication_status_is_rejected(): void
    {
        [$user, $item] = $this->medication();

        $this->actingAs($user)->put(route('checkin-entries.update', $item), [
            'checked_on' => '2026-09-07',
            'timing' => 'morning',
            'checked' => '1',
            'status' => 'unknown',
        ])->assertSessionHasErrors('status');

        $this->assertDatabaseCount('checkin_entries', 0);
    }

    public function test_missed_status_is_recorded_but_not_counted_as_taken(): void
    {
        Carbon::setTestNow('2026-09-07 10:00:00');
        [$user, $item] = $this->medication();
        CheckinEntry::create([
            'checkin_item_id' => $item->id,
            'user_id' => $user->id,
            'checked_on' => '2026-09-07',
            'timing' => 'morning',
            'status' => 'missed',
            'confirmed_at' => now(),
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('お薬の状況を記録できましたね')
            ->assertDontSee('まだ確認していない分があります');
        $this->get(route('reviews.index', ['period' => 'week', 'date' => '2026-09-07']))
            ->assertOk()
            ->assertSee('0 / 7回');
    }

    private function medication(): array
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->active()->create([
            'uses_checkins' => true,
            'uses_calendar' => true,
        ]);
        $item = CheckinItem::create([
            'project_id' => $project->id,
            'title' => '朝のお薬',
            'kind' => 'medication',
            'medication_timings' => ['morning'],
            'schedule_type' => 'daily',
            'is_active' => true,
        ]);

        return [$user, $item];
    }
}
