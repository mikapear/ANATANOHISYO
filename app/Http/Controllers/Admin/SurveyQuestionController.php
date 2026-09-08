<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SurveyDefinition;
use App\Models\SurveyQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SurveyQuestionController extends Controller
{
    public function store(Request $request, SurveyDefinition $surveyDefinition): RedirectResponse
    {
        $this->ensureEditable($surveyDefinition);
        $validated = $this->validateQuestion($request, $surveyDefinition);

        $surveyDefinition->questions()->create($validated + [
            'options' => $this->options($validated['response_type'], $request->string('options_text')->toString()),
            'position' => ($surveyDefinition->questions()->max('position') ?? 0) + 1,
        ]);

        return back()->with('status', '質問を追加しました。');
    }

    public function update(Request $request, SurveyQuestion $surveyQuestion): RedirectResponse
    {
        $definition = $surveyQuestion->definition;
        $this->ensureEditable($definition);
        $validated = $this->validateQuestion($request, $definition, $surveyQuestion);

        $surveyQuestion->update($validated + [
            'options' => $this->options($validated['response_type'], $request->string('options_text')->toString()),
        ]);

        return back()->with('status', '質問を更新しました。');
    }

    public function destroy(SurveyQuestion $surveyQuestion): RedirectResponse
    {
        $this->ensureEditable($surveyQuestion->definition);
        $surveyQuestion->delete();

        return back()->with('status', '質問を削除しました。');
    }

    private function validateQuestion(Request $request, SurveyDefinition $definition, ?SurveyQuestion $question = null): array
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:80', 'alpha_dash', Rule::unique('survey_questions')->where('survey_definition_id', $definition->id)->ignore($question)],
            'prompt' => ['required', 'string', 'max:1000'],
            'response_type' => ['required', Rule::in(['single_choice', 'multiple_choice', 'scale', 'number', 'text', 'textarea'])],
            'is_required' => ['nullable', 'boolean'],
            'options_text' => ['nullable', 'string', 'max:4000'],
        ]);

        if (in_array($validated['response_type'], ['single_choice', 'multiple_choice', 'scale'], true)
            && count($this->optionLines($request->string('options_text')->toString())) < 2) {
            abort(422, '選択式の質問には選択肢を2つ以上入力してください。');
        }

        unset($validated['options_text']);
        $validated['is_required'] = $request->boolean('is_required');

        return $validated;
    }

    private function options(string $type, string $text): ?array
    {
        if (! in_array($type, ['single_choice', 'multiple_choice', 'scale'], true)) {
            return null;
        }

        return collect($this->optionLines($text))->mapWithKeys(
            fn (string $label, int $index) => [(string) ($index + 1) => $label]
        )->all();
    }

    private function optionLines(string $text): array
    {
        return collect(preg_split('/\R/u', $text) ?: [])->map(fn ($line) => trim($line))->filter()->values()->all();
    }

    private function ensureEditable(SurveyDefinition $definition): void
    {
        abort_if($definition->assignments()->exists(), 409, '割り当て済みのアンケートは変更できません。');
    }
}
