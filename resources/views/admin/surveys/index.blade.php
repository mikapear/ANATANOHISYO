<x-site-app title="アンケート管理 | ANATANOHISHO">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div><p class="text-xs font-semibold tracking-wide text-stone-500">管理者専用</p><h1 class="mt-1 text-2xl font-bold text-indigo-950">アンケート管理</h1><p class="mt-2 text-sm text-stone-600">質問内容を下書きし、確認後に有効にできます。</p></div>
        <a href="{{ route('admin.usage.index') }}" class="secondary-action">利用状況一覧へ</a>
    </div>

    <section class="mt-7 rounded-3xl border border-stone-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-bold text-stone-800">新しい下書き</h2>
        <form method="POST" action="{{ route('admin.surveys.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2">@csrf
            <label class="text-sm font-semibold text-stone-600">管理コード<input class="form-input mt-1" name="code" value="{{ old('code') }}" placeholder="例：self_management"></label>
            <label class="text-sm font-semibold text-stone-600">タイトル<input class="form-input mt-1" name="title" value="{{ old('title') }}"></label>
            <label class="text-sm font-semibold text-stone-600 sm:col-span-2">説明<textarea class="form-input mt-1" name="description" rows="2">{{ old('description') }}</textarea></label>
            <div class="sm:col-span-2"><button class="primary-action">下書きを作る</button></div>
        </form>
        <x-input-error :messages="$errors->all()" class="mt-3" />
    </section>

    @if($definitions->isNotEmpty())
        <div class="mt-6 flex flex-wrap gap-2">
            @foreach($definitions as $definition)
                <a href="{{ route('admin.surveys.index', ['survey' => $definition]) }}" class="rounded-full border px-4 py-2 text-sm font-semibold {{ $selected?->id === $definition->id ? 'border-violet-300 bg-violet-100 text-violet-900' : 'border-stone-200 bg-white text-stone-600' }}">{{ $definition->title }}（{{ $definition->questions_count }}問）</a>
            @endforeach
        </div>
    @endif

    @if($selected)
        @php($locked = $selected->assignments_count > 0)
        <section class="mt-6 rounded-3xl border border-violet-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3"><h2 class="text-lg font-bold text-stone-800">基本設定</h2><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $selected->is_active ? 'bg-green-100 text-green-800' : 'bg-stone-100 text-stone-600' }}">{{ $selected->is_active ? '有効' : '下書き' }}</span></div>
            @if($locked)<p class="mt-3 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900">すでに割り当て済みのため、質問内容は変更できません。</p>@endif
            <form method="POST" action="{{ route('admin.surveys.update', $selected) }}" class="mt-4 grid gap-3 sm:grid-cols-2">@csrf @method('PUT')
                <label class="text-sm font-semibold text-stone-600">管理コード<input class="form-input mt-1" name="code" value="{{ $selected->code }}" @disabled($locked)></label>
                <label class="text-sm font-semibold text-stone-600">タイトル<input class="form-input mt-1" name="title" value="{{ $selected->title }}" @disabled($locked)></label>
                <label class="text-sm font-semibold text-stone-600 sm:col-span-2">説明<textarea class="form-input mt-1" name="description" rows="2" @disabled($locked)>{{ $selected->description }}</textarea></label>
                <label class="flex items-center gap-2 text-sm text-stone-700 sm:col-span-2"><input type="checkbox" name="is_active" value="1" @checked($selected->is_active) @disabled($locked)> このアンケートを有効にする</label>
                @unless($locked)<div class="sm:col-span-2"><button class="primary-action">設定を保存</button></div>@endunless
            </form>
        </section>

        <section class="mt-6 rounded-3xl border border-pink-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-bold text-stone-800">利用者へ割り当てる</h2>
            @if(! $selected->is_active)
                <p class="mt-3 text-sm text-stone-500">質問を確認してアンケートを有効にすると、割り当てられます。</p>
            @elseif($users->isEmpty())
                <p class="mt-3 text-sm text-stone-500">割り当てられる一般ユーザーがいません。</p>
            @else
                <p class="mt-2 text-sm text-stone-600">開始日を基準に、開始時・4週後・8週後の3回を設定します。同じ人へ再度実行しても重複しません。</p>
                <form method="POST" action="{{ route('admin.survey-assignments.store', $selected) }}" class="mt-4" x-data="{ selected: [] }">@csrf
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-stone-700"><span x-text="selected.length">0</span>人を選択中</p>
                        <div class="flex gap-2"><button type="button" class="secondary-action" @click="selected = {{ $users->pluck('id')->values()->toJson() }}">全員を選択</button><button type="button" class="secondary-action" @click="selected = []">選択解除</button></div>
                    </div>
                    <div class="mt-3 grid max-h-64 gap-2 overflow-y-auto rounded-2xl border border-stone-200 p-3 sm:grid-cols-2">
                        @foreach($users as $user)
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-stone-100 p-3 hover:bg-violet-50"><input class="mt-1" type="checkbox" name="user_ids[]" value="{{ $user->id }}" x-model.number="selected"><span><span class="block text-sm font-semibold text-stone-800">{{ $user->name }}</span><span class="block text-xs text-stone-500">{{ $user->email }}</span></span></label>
                        @endforeach
                    </div>
                    <label class="mt-4 block max-w-sm text-sm font-semibold text-stone-600">開始日<input class="form-input mt-1" type="date" name="start_date" value="{{ today()->toDateString() }}" required></label>
                    <div class="mt-4"><button class="primary-action" :disabled="selected.length === 0" :class="{ 'opacity-50 cursor-not-allowed': selected.length === 0 }">選択した利用者に3回分を割り当てる</button></div>
                </form>
            @endif
        </section>

        @if($selected->assignments->isNotEmpty())
            @php($phaseLabels = ['baseline' => '開始時', 'week4' => '4週', 'week8' => '8週'])
            @php($statusLabels = ['pending' => '回答前', 'in_progress' => '回答中', 'completed' => '完了'])
            <section class="mt-6 overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-stone-100 px-5 py-4"><div><h2 class="text-lg font-bold text-stone-800">割り当て状況</h2><p class="mt-1 text-xs text-pink-700">CSVの自由記述には個人情報が含まれる可能性があります。研究利用前に必ず確認してください。</p></div><a class="secondary-action" href="{{ route('admin.surveys.export', $selected) }}">匿名化CSVを出力</a></div>
                <div class="overflow-x-auto"><table class="min-w-full divide-y divide-stone-200 text-left text-sm"><thead class="bg-stone-50 text-xs text-stone-500"><tr><th class="px-5 py-3">利用者</th><th class="px-4 py-3">時期</th><th class="px-4 py-3">回答予定日</th><th class="px-5 py-3">状態</th></tr></thead><tbody class="divide-y divide-stone-100">
                    @foreach($selected->assignments->sortBy('due_on') as $assignment)<tr><td class="px-5 py-4"><a href="{{ route('admin.survey-results.show', $assignment) }}" class="font-semibold text-violet-800 hover:underline">{{ $assignment->user->name }}</a><p class="text-xs text-stone-500">{{ $assignment->user->email }}</p></td><td class="px-4 py-4">{{ $phaseLabels[$assignment->phase] ?? $assignment->phase }}</td><td class="px-4 py-4">{{ $assignment->due_on->format('Y年n月j日') }}</td><td class="px-5 py-4">{{ $statusLabels[$assignment->status] ?? $assignment->status }}</td></tr>@endforeach
                </tbody></table></div>
            </section>
        @endif

        <section class="mt-6 space-y-4">
            <h2 class="text-lg font-bold text-stone-800">質問一覧</h2>
            @foreach($selected->questions as $question)
                <details class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm"><summary class="cursor-pointer font-semibold text-stone-800">{{ $loop->iteration }}. {{ $question->prompt }}</summary>
                    <form method="POST" action="{{ route('admin.survey-questions.update', $question) }}" class="mt-4 grid gap-3 sm:grid-cols-2">@csrf @method('PUT')
                        @include('admin.surveys.partials.question-fields', ['question' => $question, 'locked' => $locked])
                        @unless($locked)<div class="flex gap-3 sm:col-span-2"><button class="primary-action">質問を保存</button></div>@endunless
                    </form>
                    @unless($locked)<form method="POST" action="{{ route('admin.survey-questions.destroy', $question) }}" class="mt-3">@csrf @method('DELETE')<button class="text-sm font-semibold text-red-700" onclick="return confirm('この質問を削除しますか？')">削除</button></form>@endunless
                </details>
            @endforeach
        </section>

        @unless($locked)
            <section class="mt-6 rounded-3xl border border-amber-200 bg-white p-5 shadow-sm"><h2 class="text-lg font-bold text-stone-800">質問を追加</h2>
                <form method="POST" action="{{ route('admin.survey-questions.store', $selected) }}" class="mt-4 grid gap-3 sm:grid-cols-2">@csrf
                    @include('admin.surveys.partials.question-fields', ['question' => null, 'locked' => false])
                    <div class="sm:col-span-2"><button class="primary-action">質問を追加</button></div>
                </form>
            </section>
        @endunless
    @endif
</x-site-app>
