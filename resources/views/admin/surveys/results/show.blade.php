<x-site-app title="アンケート回答 | ANATANOHISHO">
    @php($phaseLabels = ['baseline' => '開始時', 'week4' => '4週', 'week8' => '8週'])
    @php($statusLabels = ['pending' => '回答前', 'in_progress' => '回答中', 'completed' => '完了'])
    @php($answers = $surveyAssignment->answers->keyBy('survey_question_id'))
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('admin.surveys.index', ['survey' => $surveyAssignment->definition]) }}" class="text-sm font-semibold text-violet-800">← アンケート管理へ</a>
        <div class="mt-4"><p class="text-xs font-semibold text-stone-500">管理者専用・回答内容</p><h1 class="mt-1 text-2xl font-bold text-indigo-950">{{ $surveyAssignment->definition->title }}</h1></div>
        <section class="mt-5 grid gap-3 rounded-3xl border border-stone-200 bg-white p-5 text-sm sm:grid-cols-2">
            <p><span class="text-stone-500">利用者：</span>{{ $surveyAssignment->user->name }}</p>
            <p><span class="text-stone-500">研究用ID：</span>{{ $surveyAssignment->user->research_code }}</p>
            <p><span class="text-stone-500">時期：</span>{{ $phaseLabels[$surveyAssignment->phase] ?? $surveyAssignment->phase }}</p>
            <p><span class="text-stone-500">状態：</span>{{ $statusLabels[$surveyAssignment->status] ?? $surveyAssignment->status }}</p>
            <p><span class="text-stone-500">開始：</span>{{ $surveyAssignment->started_at?->format('Y年n月j日 H:i') ?? '未開始' }}</p>
            <p><span class="text-stone-500">完了：</span>{{ $surveyAssignment->completed_at?->format('Y年n月j日 H:i') ?? '未完了' }}</p>
        </section>
        <section class="mt-5 space-y-3">
            @foreach($surveyAssignment->definition->questions as $question)
                @php($value = $answers->get($question->id)?->response['value'] ?? null)
                <article class="rounded-2xl border border-stone-200 bg-white p-5">
                    <p class="font-semibold text-stone-800">{{ $loop->iteration }}. {{ $question->prompt }}</p>
                    <p class="mt-3 whitespace-pre-wrap text-stone-700">@if(is_array($value)){{ collect($value)->map(fn($item) => $question->options[$item] ?? $item)->join('、') }}@elseif($value !== null && $value !== ''){{ $question->options[$value] ?? $value }}@else<span class="text-stone-400">未回答</span>@endif</p>
                </article>
            @endforeach
        </section>
    </div>
</x-site-app>
