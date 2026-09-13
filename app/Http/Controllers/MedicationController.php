<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MedicationController extends Controller
{
    private const TIMINGS = ['morning', 'noon', 'evening', 'bedtime'];

    public function create(): View
    {
        return view('medications.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['medication_type' => $request->input('medication_type', 'scheduled')]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'medication_type' => ['required', Rule::in(['scheduled', 'as_needed'])],
            'medication_timings' => ['nullable', 'required_if:medication_type,scheduled', 'array', 'min:1'],
            'medication_timings.*' => [Rule::in(self::TIMINGS)],
            'dose_amount' => ['nullable', 'numeric', 'min:0.01', 'max:999999.99'],
            'dose_unit' => ['nullable', 'string', 'max:30'],
            'medication_instructions' => ['nullable', 'string', 'max:1000'],
            'medication_precautions' => ['nullable', 'string', 'max:1000'],
            'schedule_type' => ['required', Rule::in(['daily', 'weekdays', 'cycle'])],
            'weekdays' => ['nullable', 'required_if:schedule_type,weekdays', 'array', 'min:1'],
            'weekdays.*' => ['integer', 'between:0,6'],
            'cycle_on_days' => ['nullable', 'required_if:schedule_type,cycle', 'integer', 'min:1', 'max:365'],
            'cycle_rest_days' => ['nullable', 'required_if:schedule_type,cycle', 'integer', 'min:1', 'max:365'],
            'starts_on' => ['nullable', 'required_if:schedule_type,cycle', 'date_format:Y-m-d'],
            'ends_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
        ], [
            'medication_timings.required_if' => '服用する時間帯を1つ以上選んでください。',
            'weekdays.required_if' => '服用する曜日を1つ以上選んでください。',
            'cycle_on_days.required_if' => '内服する日数を入力してください。',
            'cycle_rest_days.required_if' => '休薬する日数を入力してください。',
            'starts_on.required_if' => '周期の開始日を入力してください。',
            'ends_on.after_or_equal' => '終了日は開始日以降にしてください。',
        ]);

        if ($validated['schedule_type'] === 'cycle' && empty($validated['starts_on'])) {
            throw ValidationException::withMessages(['starts_on' => '周期の開始日を入力してください。']);
        }

        DB::transaction(function () use ($request, $validated): void {
            $project = Project::where('user_id', $request->user()->id)
                ->where('uses_checkins', true)
                ->where('status', 'active')
                ->whereHas('checkinItems', fn ($query) => $query->where('kind', 'medication'))
                ->orderBy('id')
                ->first();

            $project ??= Project::where('user_id', $request->user()->id)
                ->where('uses_checkins', true)
                ->where('status', 'active')
                ->where('template', 'checkin')
                ->orderBy('id')
                ->first();

            $project ??= $request->user()->projects()->create([
                'name' => 'おくすり',
                'description' => '毎日のお薬を確認するためのまとまりです。',
                'status' => 'active',
                'template' => 'checkin',
                'uses_todos' => false,
                'uses_checkins' => true,
                'uses_activity_logs' => false,
                'uses_calendar' => true,
            ]);

            $isAsNeeded = $validated['medication_type'] === 'as_needed';

            $project->checkinItems()->create([
                'title' => $validated['title'],
                'kind' => 'medication',
                'is_as_needed' => $isAsNeeded,
                'medication_timings' => $isAsNeeded ? [] : array_values(array_unique($validated['medication_timings'])),
                'dose_amount' => $validated['dose_amount'] ?? null,
                'dose_unit' => $validated['dose_unit'] ?? null,
                'medication_instructions' => $validated['medication_instructions'] ?? null,
                'medication_precautions' => $validated['medication_precautions'] ?? null,
                'schedule_type' => $isAsNeeded ? 'daily' : $validated['schedule_type'],
                'weekdays' => $validated['schedule_type'] === 'weekdays'
                    ? array_values(array_unique(array_map('intval', $validated['weekdays'])))
                    : null,
                'cycle_on_days' => $validated['schedule_type'] === 'cycle' ? (int) $validated['cycle_on_days'] : null,
                'cycle_rest_days' => $validated['schedule_type'] === 'cycle' ? (int) $validated['cycle_rest_days'] : null,
                'starts_on' => $validated['starts_on'] ?? null,
                'ends_on' => $validated['ends_on'] ?? null,
                'position' => ((int) $project->checkinItems()->max('position')) + 1,
                'is_active' => true,
            ]);
        });

        return to_route('care.index')->with('status', 'お薬を登録しました。今日の画面で確認できます。');
    }
}
