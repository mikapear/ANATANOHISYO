<?php

namespace App\Http\Controllers;

use App\Models\AsNeededMedicationUsage;
use App\Models\CheckinItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AsNeededMedicationUsageController extends Controller
{
    public function store(Request $request, CheckinItem $checkinItem): RedirectResponse
    {
        $checkinItem->loadMissing('project');
        abort_unless($checkinItem->project->user_id === $request->user()->id && $checkinItem->kind === 'medication' && $checkinItem->is_as_needed && $checkinItem->is_active, 403);

        $validated = $request->validate([
            'used_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $checkinItem->asNeededUsages()->create([
            'user_id' => $request->user()->id,
            'used_at' => $validated['used_at'] ?? now(),
            'note' => $validated['note'] ?? null,
        ]);

        return back()->with('status', $checkinItem->title.'を使った時刻を記録しました。');
    }

    public function destroy(Request $request, AsNeededMedicationUsage $asNeededMedicationUsage): RedirectResponse
    {
        abort_unless($asNeededMedicationUsage->user_id === $request->user()->id, 403);
        $asNeededMedicationUsage->delete();

        return back()->with('status', '頓服の記録を取り消しました。');
    }
}
