<x-site-app title="アンケート | ANATANOHISHO">
    @php
        $savedAnswers = $surveyAssignment->answers->keyBy('survey_question_id');
        $phaseLabels = ['baseline' => '開始時', 'week4' => '4週', 'week8' => '8週'];
    @endphp

    <div class="mx-auto max-w-2xl">
        <p class="text-xs font-semibold tracking-wide text-stone-500">{{ $phaseLabels[$surveyAssignment->phase] ?? $surveyAssignment->phase }}アンケート</p>
        <h1 class="mt-1 text-2xl font-bold text-indigo-950">{{ $surveyAssignment->definition->title }}</h1>
        @if($surveyAssignment->definition->description)<p class="mt-3 text-sm leading-6 text-stone-600">{{ $surveyAssignment->definition->description }}</p>@endif
        <p class="mt-2 text-xs text-stone-500">回答期限：{{ $surveyAssignment->due_on->format('Y年n月j日') }}　途中保存できます。</p>

        <form method="POST" action="{{ route('surveys.update', $surveyAssignment) }}" class="mt-7 space-y-5">
            @csrf @method('PUT')
            @foreach($surveyAssignment->definition->questions as $question)
                @php($saved = old("answers.{$question->id}", $savedAnswers->get($question->id)?->response['value'] ?? null))
                <fieldset class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm">
                    <legend class="px-1 font-semibold text-stone-800">{{ $loop->iteration }}. {{ $question->prompt }} @if($question->is_required)<span class="text-pink-700">*</span>@endif</legend>
                    <div class="mt-4 space-y-2">
                        @if(in_array($question->response_type, ['single_choice', 'scale'], true))
                            @foreach($question->options ?? [] as $value => $label)
                                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-stone-200 px-4 py-3"><input type="radio" name="answers[{{ $question->id }}]" value="{{ $value }}" @checked((string) $saved === (string) $value)><span>{{ $label }}</span></label>
                            @endforeach
                        @elseif($question->response_type === 'multiple_choice')
                            @foreach($question->options ?? [] as $value => $label)
                                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-stone-200 px-4 py-3"><input type="checkbox" name="answers[{{ $question->id }}][]" value="{{ $value }}" @checked(in_array((string) $value, array_map('strval', (array) $saved), true))><span>{{ $label }}</span></label>
                            @endforeach
                        @elseif($question->response_type === 'number')
                            <input class="form-input" type="number" step="any" name="answers[{{ $question->id }}]" value="{{ $saved }}">
                        @elseif($question->response_type === 'textarea')
                            <textarea class="form-input" rows="4" maxlength="4000" name="answers[{{ $question->id }}]">{{ $saved }}</textarea>
                        @else
                            <input class="form-input" type="text" maxlength="4000" name="answers[{{ $question->id }}]" value="{{ $saved }}">
                        @endif
                    </div>
                    <x-input-error :messages="$errors->get('answer')" class="mt-2" />
                </fieldset>
            @endforeach

            <div class="flex flex-wrap justify-between gap-3">
                <button type="submit" name="action" value="save" class="secondary-action">途中保存する</button>
                <button type="submit" name="action" value="complete" class="primary-action">回答を完了する</button>
            </div>
        </form>
    </div>
</x-site-app>
