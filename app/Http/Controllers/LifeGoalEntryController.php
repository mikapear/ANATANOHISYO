<?php

namespace App\Http\Controllers;

use App\Models\LifeGoal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class LifeGoalEntryController extends Controller
{
    public function update(Request $request, LifeGoal $goal): RedirectResponse
    {
        abort_unless($goal->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'recorded_on' => ['required', 'date'],
            'status' => ['required', Rule::in(['completed', 'partial', 'rest'])],
            'actual_amount' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'nutrition_type' => ['nullable', Rule::in(['breakfast', 'lunch', 'dinner', 'snack', 'hydration', 'other'])],
            'food_details' => ['nullable', 'string', 'max:1000'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $recordedOn = Carbon::parse($validated['recorded_on'])->startOfDay();

        if (! $goal->isScheduledFor($recordedOn)) {
            return back()->withErrors(['goal' => 'この日は目標の予定日ではありません。']);
        }

        $goal->entries()->updateOrCreate(
            ['recorded_on' => $recordedOn->toDateString()],
            [
                'user_id' => $request->user()->id,
                'status' => $validated['status'],
                'actual_amount' => $validated['actual_amount'] ?? null,
                'nutrition_type' => $goal->category === 'nutrition' ? ($validated['nutrition_type'] ?? null) : null,
                'food_details' => $goal->category === 'nutrition' ? ($validated['food_details'] ?? null) : null,
                'note' => $validated['note'] ?? null,
            ]
        );

        return back()->with('status', 'goal-entry-updated');
    }
}