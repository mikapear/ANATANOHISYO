<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SurveyDefinition;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SurveyExportController extends Controller
{
    public function __invoke(SurveyDefinition $surveyDefinition): StreamedResponse
    {
        $surveyDefinition->load(['questions', 'assignments.user', 'assignments.answers']);
        $filename = 'survey-'.$surveyDefinition->code.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($surveyDefinition) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, [
                'research_id', 'survey_code', 'survey_version', 'phase', 'due_on',
                'status', 'started_at', 'completed_at',
                ...$surveyDefinition->questions->pluck('key')->all(),
            ]);

            foreach ($surveyDefinition->assignments->sortBy([['user_id', 'asc'], ['due_on', 'asc']]) as $assignment) {
                $answers = $assignment->answers->keyBy('survey_question_id');
                $row = [
                    $assignment->user->research_code,
                    $surveyDefinition->code,
                    $surveyDefinition->version,
                    $assignment->phase,
                    $assignment->due_on->toDateString(),
                    $assignment->status,
                    $assignment->started_at?->toIso8601String(),
                    $assignment->completed_at?->toIso8601String(),
                ];

                foreach ($surveyDefinition->questions as $question) {
                    $value = $answers->get($question->id)?->response['value'] ?? null;
                    $row[] = $this->safeCell(is_array($value) ? implode('|', $value) : $value);
                }

                fputcsv($stream, $row);
            }

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function safeCell(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/^[=+\-@]/', $value)) {
            return chr(39).$value;
        }

        return $value;
    }
}
