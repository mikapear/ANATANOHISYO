<?php

namespace App\Http\Controllers;

use App\Models\LifeGoal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LifeGoalController extends Controller
{
    public function index(Request $request): View
    {
        $goals = LifeGoal::where('user_id', $request->user()->id)
            ->orderByDesc('is_active')
            ->orderBy('title')
            ->get()
            ->groupBy('category');

        return view('goals.index', compact('goals'));
    }

    public function manage(Request $request, string $category): View
    {
        abort_unless(in_array($category, ['exercise', 'nutrition', 'other'], true), 404);

        $goals = LifeGoal::where('user_id', $request->user()->id)
            ->where('category', $category)
            ->with('project')
            ->orderByDesc('is_active')
            ->orderBy('title')
            ->get();

        return view('goals.manage', compact('goals', 'category'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateGoal($request);
        $request->user()->lifeGoals()->create($this->attributes($validated));

        return back()->with('status', '生活目標を登録しました。');
    }

    public function update(Request $request, LifeGoal $goal): RedirectResponse
    {
        $this->authorizeGoal($request, $goal);
        $validated = $this->validateGoal($request);
        $goal->update($this->attributes($validated) + ['is_active' => $request->boolean('is_active')]);

        return back()->with('status', '生活目標を更新しました。');
    }

    public function destroy(Request $request, LifeGoal $goal): RedirectResponse
    {
        $this->authorizeGoal($request, $goal);
        $goal->delete();

        return back()->with('status', '生活目標を削除しました。');
    }

    private function validateGoal(Request $request): array
    {
        return $request->validate([
            'category' => ['required', Rule::in(['exercise', 'nutrition', 'other'])],
            'title' => ['required', 'string', 'max:100'],
            'target_amount' => ['nullable', 'numeric', 'min:0.01', 'max:999999.99'],
            'target_unit' => ['nullable', 'required_with:target_amount', 'string', 'max:30'],
            'time_of_day' => ['required', Rule::in(['anytime', 'morning', 'noon', 'evening', 'bedtime'])],
            'schedule_type' => ['required', Rule::in(['daily', 'weekdays'])],
            'weekdays' => ['nullable', 'required_if:schedule_type,weekdays', 'array', 'min:1'],
            'weekdays.*' => ['integer', 'between:0,6'],
            'starts_on' => ['nullable', 'date_format:Y-m-d'],
            'ends_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'decided_with' => ['required', Rule::in(['self', 'family', 'clinician'])],
            'project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')->where('user_id', $request->user()->id)],
            'note' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'weekdays.required_if' => '取り組む曜日を1つ以上選んでください。',
            'target_unit.required_with' => '目標量の単位を入力してください。',
            'ends_on.after_or_equal' => '終了日は開始日以降にしてください。',
        ]);
    }

    private function attributes(array $validated): array
    {
        return [
            'project_id' => $validated['project_id'] ?? null,
            'category' => $validated['category'],
            'title' => $validated['title'],
            'target_amount' => $validated['target_amount'] ?? null,
            'target_unit' => $validated['target_unit'] ?? null,
            'time_of_day' => $validated['time_of_day'],
            'schedule_type' => $validated['schedule_type'],
            'weekdays' => $validated['schedule_type'] === 'weekdays'
                ? array_values(array_unique(array_map('intval', $validated['weekdays'])))
                : null,
            'starts_on' => $validated['starts_on'] ?? null,
            'ends_on' => $validated['ends_on'] ?? null,
            'decided_with' => $validated['decided_with'],
            'note' => $validated['note'] ?? null,
        ];
    }

    private function authorizeGoal(Request $request, LifeGoal $goal): void
    {
        abort_unless($goal->user_id === $request->user()->id, 403);
    }
}
