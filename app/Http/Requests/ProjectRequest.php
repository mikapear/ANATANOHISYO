<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $template = $this->string('template')->toString() ?: 'planning';

        $this->merge([
            'template' => $template,
            'uses_todos' => $this->has('uses_todos') ? $this->boolean('uses_todos') : $template === 'planning',
            'uses_checkins' => $this->has('uses_checkins') ? $this->boolean('uses_checkins') : $template === 'checkin',
            'uses_activity_logs' => $this->has('uses_activity_logs') ? $this->boolean('uses_activity_logs') : $template !== 'planning',
            'uses_calendar' => $this->has('uses_calendar') ? $this->boolean('uses_calendar') : true,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(['active', 'completed', 'on_hold'])],
            'template' => ['required', Rule::in(['planning', 'checkin', 'journal'])],
            'uses_todos' => ['boolean'],
            'uses_checkins' => ['boolean'],
            'uses_activity_logs' => ['boolean'],
            'uses_calendar' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '暮らしの予定名',
            'description' => '説明',
            'start_date' => '開始日',
            'due_date' => '期限',
            'status' => '状態',
            'template' => '使い方',
        ];
    }
}

