<?php

namespace App\Http\Controllers;

use App\Models\CheckinItem;
use App\Services\UsageRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CheckinEntryController extends Controller
{
    public function update(Request $request, CheckinItem $checkinItem): RedirectResponse
    {
        $checkinItem->loadMissing('project');
        abort_unless(
            $checkinItem->project->user_id === $request->user()->id
                && $checkinItem->project->uses_checkins
                && $checkinItem->is_active,
            403
        );

        $request->merge(['timing' => $request->input('timing', 'once')]);
        $allowedTimings = $checkinItem->kind === 'medication'
            ? ($checkinItem->medication_timings ?? [])
            : ['once'];

        $validated = $request->validate([
            'checked_on' => ['required', 'date_format:Y-m-d'],
            'timing' => ['required', Rule::in($allowedTimings)],
            'checked' => ['required', 'boolean'],
            'status' => ['nullable', Rule::in(['taken', 'missed', 'skipped', 'later'])],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $checkedDate = Carbon::createFromFormat('!Y-m-d', $validated['checked_on']);
        if (! $checkinItem->isScheduledFor($checkedDate)) {
            throw ValidationException::withMessages([
                'checked_on' => 'この日は実施予定日に設定されていません。',
            ]);
        }

        $entryKey = [
            'checked_on' => $validated['checked_on'],
            'timing' => $validated['timing'],
        ];

        if (! $request->boolean('checked')) {
            $checkinItem->entries()->where($entryKey)->delete();

            return back()->with('status', '記録を取り消しました。');
        }

        $status = $checkinItem->kind === 'medication'
            ? ($validated['status'] ?? 'taken')
            : 'taken';
        $entry = $checkinItem->entries()->firstOrNew($entryKey);
        $statusChanged = ! $entry->exists || $entry->status !== $status;
        $entry->fill([
            'user_id' => $request->user()->id,
            'status' => $status,
            'note' => $validated['note'] ?? null,
        ]);

        if ($statusChanged) {
            $entry->confirmed_at = now();
        }

        if (! $entry->exists || $entry->isDirty()) {
            $entry->save();
        }

        if ($checkinItem->kind === 'medication' && $statusChanged) {
            app(UsageRecorder::class)->record(
                $request->user(),
                'medication.checked',
                $checkinItem,
                ['timing' => $validated['timing'], 'status' => $status]
            );
        }

        return back()->with('status', $checkinItem->kind === 'medication'
            ? 'お薬の状況を記録しました。'
            : '花丸を記録しました。');
    }
}
