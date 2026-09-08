<?php

namespace App\Http\Controllers;

use App\Models\CheckinItem;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CheckinItemController extends Controller
{
    private const TIMINGS = ['morning', 'noon', 'evening', 'bedtime'];

    public function store(Request $request, Project $project): RedirectResponse
    {
        abort_unless($project->user_id === $request->user()->id && $project->uses_checkins, 403);
        $request->merge([
            'kind' => $request->input('kind', 'checkin'),
            'schedule_type' => $request->input('schedule_type', 'daily'),
        ]);
        $validated = $this->validateItem($request);

        $project->checkinItems()->create($this->attributes($validated) + [
            'position' => ((int) $project->checkinItems()->max('position')) + 1,
        ]);

        return back()->with('status', $validated['kind'] === 'medication'
            ? 'お薬を登録しました。'
            : '毎日チェックの項目を追加しました。');
    }

    public function update(Request $request, CheckinItem $checkinItem): RedirectResponse
    {
        $this->authorizeItem($request, $checkinItem);
        $request->merge([
            'kind' => $request->input('kind', $checkinItem->kind),
            'schedule_type' => $request->input('schedule_type', $checkinItem->schedule_type),
            'medication_timings' => $request->input('medication_timings', $checkinItem->medication_timings),
            'dose_amount' => $request->input('dose_amount', $checkinItem->dose_amount),
            'dose_unit' => $request->input('dose_unit', $checkinItem->dose_unit),
            'medication_instructions' => $request->input('medication_instructions', $checkinItem->medication_instructions),
            'medication_precautions' => $request->input('medication_precautions', $checkinItem->medication_precautions),
            'weekdays' => $request->input('weekdays', $checkinItem->weekdays),
            'cycle_on_days' => $request->input('cycle_on_days', $checkinItem->cycle_on_days),
            'cycle_rest_days' => $request->input('cycle_rest_days', $checkinItem->cycle_rest_days),
            'starts_on' => $request->input('starts_on', $checkinItem->starts_on?->toDateString()),
            'ends_on' => $request->input('ends_on', $checkinItem->ends_on?->toDateString()),
        ]);
        $validated = $this->validateItem($request, true);
        $checkinItem->update($this->attributes($validated) + [
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', '登録内容を更新しました。');
    }

    public function destroy(Request $request, CheckinItem $checkinItem): RedirectResponse
    {
        $this->authorizeItem($request, $checkinItem);
        $checkinItem->delete();

        return back()->with('status', '登録項目を削除しました。');
    }

    private function validateItem(Request $request, bool $updating = false): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'kind' => ['required', Rule::in(['checkin', 'medication'])],
            'medication_timings' => ['nullable', 'required_if:kind,medication', 'array', 'min:1'],
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
            'is_active' => [$updating ? 'nullable' : 'sometimes', 'boolean'],
        ], [
            'medication_timings.required_if' => '服用する時間帯を1つ以上選んでください。',
            'weekdays.required_if' => '実施する曜日を1つ以上選んでください。',
            'cycle_on_days.required_if' => '内服する日数を入力してください。',
            'cycle_rest_days.required_if' => '休薬する日数を入力してください。',
            'starts_on.required_if' => '周期の開始日を入力してください。',
            'ends_on.after_or_equal' => '終了日は開始日以降にしてください。',
        ]);

        if ($validated['schedule_type'] === 'cycle' && $validated['kind'] !== 'medication') {
            throw ValidationException::withMessages([
                'schedule_type' => '内服・休薬周期は、お薬にのみ設定できます。',
            ]);
        }

        return $validated;
    }

    private function attributes(array $validated): array
    {
        return [
            'title' => $validated['title'],
            'kind' => $validated['kind'],
            'medication_timings' => $validated['kind'] === 'medication'
                ? array_values(array_unique($validated['medication_timings']))
                : null,
            'dose_amount' => $validated['kind'] === 'medication' ? ($validated['dose_amount'] ?? null) : null,
            'dose_unit' => $validated['kind'] === 'medication' ? ($validated['dose_unit'] ?? null) : null,
            'medication_instructions' => $validated['kind'] === 'medication'
                ? ($validated['medication_instructions'] ?? null)
                : null,
            'medication_precautions' => $validated['kind'] === 'medication'
                ? ($validated['medication_precautions'] ?? null)
                : null,
            'schedule_type' => $validated['schedule_type'],
            'weekdays' => $validated['schedule_type'] === 'weekdays'
                ? array_values(array_unique(array_map('intval', $validated['weekdays'])))
                : null,
            'cycle_on_days' => $validated['schedule_type'] === 'cycle'
                ? (int) $validated['cycle_on_days']
                : null,
            'cycle_rest_days' => $validated['schedule_type'] === 'cycle'
                ? (int) $validated['cycle_rest_days']
                : null,
            'starts_on' => $validated['starts_on'] ?? null,
            'ends_on' => $validated['ends_on'] ?? null,
        ];
    }

    private function authorizeItem(Request $request, CheckinItem $item): void
    {
        $item->loadMissing('project');
        abort_unless($item->project->user_id === $request->user()->id, 403);
    }
}
