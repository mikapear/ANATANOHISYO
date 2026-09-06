<?php

namespace App\Http\Controllers;

use App\Models\CheckinItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $checkedDate = \Illuminate\Support\Carbon::createFromFormat('!Y-m-d', $validated['checked_on']);
        if (! $checkinItem->isScheduledFor($checkedDate)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'checked_on' => 'この日は実施予定日に設定されていません。',
            ]);
        }

        $entryKey = [
            'checked_on' => $validated['checked_on'],
            'timing' => $validated['timing'],
        ];

        if (! $request->boolean('checked')) {
            $checkinItem->entries()->where($entryKey)->delete();

            return back()->with('status', '花丸を取り消しました。');
        }

        $checkinItem->entries()->updateOrCreate(
            $entryKey,
            ['user_id' => $request->user()->id, 'note' => $validated['note'] ?? null]
        );

        return back()->with('status', '花丸を記録しました。');
    }
}
