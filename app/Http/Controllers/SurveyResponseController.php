<?php

namespace App\Http\Controllers;

use App\Models\SurveyAssignment;
use App\Services\UsageRecorder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SurveyResponseController extends Controller
{
    public function edit(Request $request, SurveyAssignment $surveyAssignment): View
    {
        $this->authorizeAccess($request, $surveyAssignment);

        $surveyAssignment->load(['definition.questions', 'answers']);

        if ($surveyAssignment->status === 'pending') {
            $surveyAssignment->update(['status' => 'in_progress', 'started_at' => now()]);
            app(UsageRecorder::class)->record($request->user(), 'survey.started', $surveyAssignment);
        }

        return view('surveys.edit', compact('surveyAssignment'));
    }

    public function update(Request $request, SurveyAssignment $surveyAssignment): RedirectResponse
    {
        $this->authorizeAccess($request, $surveyAssignment);
        abort_if($surveyAssignment->status === 'completed', 409);

        $surveyAssignment->load('definition.questions');
        $complete = $request->string('action')->toString() === 'complete';
        $answers = $request->input('answers', []);

        foreach ($surveyAssignment->definition->questions as $question) {
            $value = $answers[$question->id] ?? null;
            $rules = $this->rulesFor($question->response_type, $question->options ?? []);

            if ($complete && $question->is_required) {
                array_unshift($rules['answer'], 'required');
            } else {
                array_unshift($rules['answer'], 'nullable');
            }

            $validated = Validator::make(
                ['answer' => $value],
                $rules,
                [],
                ['answer' => $question->prompt],
            )->validate();

            if ($this->isBlank($validated['answer'] ?? null)) {
                if (! $complete) {
                    $surveyAssignment->answers()->where('survey_question_id', $question->id)->delete();
                }

                continue;
            }

            $surveyAssignment->answers()->updateOrCreate(
                ['survey_question_id' => $question->id],
                ['response' => ['value' => $validated['answer']], 'answered_at' => now()],
            );
        }

        $surveyAssignment->update($complete
            ? ['status' => 'completed', 'completed_at' => now(), 'started_at' => $surveyAssignment->started_at ?? now()]
            : ['status' => 'in_progress', 'started_at' => $surveyAssignment->started_at ?? now()]);

        app(UsageRecorder::class)->record(
            $request->user(),
            $complete ? 'survey.completed' : 'survey.saved',
            $surveyAssignment,
        );

        return $complete
            ? redirect()->route('dashboard')->with('status', 'アンケートへのご協力ありがとうございました。')
            : back()->with('status', '回答を途中保存しました。');
    }

    private function authorizeAccess(Request $request, SurveyAssignment $assignment): void
    {
        abort_unless($assignment->user_id === $request->user()->id, 403);
        abort_if($assignment->available_from->isFuture(), 404);
        abort_if($assignment->available_until?->isPast(), 404);
    }

    private function rulesFor(string $type, array $options): array
    {
        return match ($type) {
            'single_choice', 'scale' => ['answer' => ['string', Rule::in(array_keys($options))]],
            'multiple_choice' => [
                'answer' => ['array'],
                'answer.*' => ['string', Rule::in(array_keys($options))],
            ],
            'number' => ['answer' => ['numeric']],
            'text', 'textarea' => ['answer' => ['string', 'max:4000']],
            default => ['answer' => ['prohibited']],
        };
    }

    private function isBlank(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }
}
