<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SurveyDefinition;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SurveyDefinitionController extends Controller
{
    public function index(Request $request): View
    {
        $definitions = SurveyDefinition::query()->withCount(['questions', 'assignments'])->latest()->get();
        $selected = $request->integer('survey')
            ? SurveyDefinition::query()->with(['questions', 'assignments.user'])->withCount('assignments')->findOrFail($request->integer('survey'))
            : $definitions->first()?->load(['questions', 'assignments.user']);
        $users = User::query()->where('is_admin', false)->orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.surveys.index', compact('definitions', 'selected', 'users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:80', 'alpha_dash', 'unique:survey_definitions,code'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:4000'],
        ]);

        $definition = SurveyDefinition::create($validated + ['version' => 1, 'is_active' => false]);

        return redirect()->route('admin.surveys.index', ['survey' => $definition])->with('status', 'アンケートの下書きを作成しました。');
    }

    public function update(Request $request, SurveyDefinition $surveyDefinition): RedirectResponse
    {
        abort_if($surveyDefinition->assignments()->exists(), 409, '割り当て済みのアンケートは変更できません。');

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:80', 'alpha_dash', Rule::unique('survey_definitions')->ignore($surveyDefinition)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:4000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $activate = $request->boolean('is_active');

        if ($activate && ! $surveyDefinition->questions()->exists()) {
            return back()->withErrors(['is_active' => '質問を1つ以上追加してから有効にしてください。']);
        }

        $surveyDefinition->update($validated + ['is_active' => $activate]);

        return back()->with('status', 'アンケート設定を更新しました。');
    }
}
