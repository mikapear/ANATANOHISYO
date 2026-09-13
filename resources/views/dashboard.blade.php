<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>今日を確認する | ANATANOHISHO</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen app-shell pb-24 text-gray-900 antialiased sm:pb-0">
    <header class="app-header sticky top-0 z-40 border-b">
        <div class="mx-auto flex min-h-16 max-w-5xl items-center justify-between px-4 py-2 sm:px-6">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 font-semibold text-indigo-900">
                <img src="{{ asset('images/brand/anatanohisyo-guide.png') }}" alt="" class="h-10 w-10 rounded-full object-cover">
                <span class="brand-name hidden sm:inline">ANATANOHISHO</span>
            </a>
            <a href="{{ route('profile.edit') }}" class="profile-shortcut sm:hidden" aria-label="プロフィールを開く">
                {{ mb_substr(Auth::user()->name, 0, 1) }}
            </a>
            <nav class="hidden items-center gap-1 text-sm font-medium sm:flex" aria-label="メインナビゲーション">
                <a class="site-nav-link site-nav-link--active" href="{{ route('dashboard') }}">今日</a>
                <a class="site-nav-link" href="{{ route('todos.index') }}">やること</a>
                <a class="site-nav-link" href="{{ route('activity-logs.index') }}">記録</a>
                <a class="site-nav-link" href="{{ route('calendar.index') }}">カレンダー</a>
                <a class="site-nav-link" href="{{ route('goals.index') }}">目標</a>
                <a class="site-nav-link" href="{{ route('profile.edit') }}">プロフィール</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="site-nav-link">ログアウト</button>
                </form>
            </nav>
        </div>
    </header>

    @php
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $priorityLabels = ['low' => '低', 'medium' => '中', 'high' => '高'];
        $formatTime = fn (?string $time) => $time ? substr($time, 0, 5) : null;
    @endphp

    <main class="mx-auto max-w-4xl px-4 py-7 sm:px-6 sm:py-10">
        <section class="flex flex-wrap items-end justify-between gap-4 text-center sm:text-left">
            <div>
                <p class="text-xs font-bold tracking-[0.18em] text-violet-600">TODAY</p>
            <h1 class="text-2xl font-bold text-indigo-950 sm:text-3xl">
                {{ $today->format('Y年n月j日') }}（{{ $weekdays[$today->dayOfWeek] }}）
            </h1>
            </div>
            <div class="today-quick-actions" aria-label="今日のクイック操作">
                <a href="{{ route('calendar.index') }}">カレンダー</a>
                <a href="{{ route('activity-logs.create', ['performed_on' => $today->toDateString()]) }}" class="today-quick-actions__primary">＋ 記録する</a>
            </div>
        </section>

        <div class="mt-6 flex items-end gap-3">
            <img src="{{ asset('images/brand/anatanohisyo-guide.png') }}" alt="ANATANOHISHOの案内役" class="h-20 w-16 shrink-0 rounded-xl object-cover sm:h-24 sm:w-20">
            <div class="guide-bubble today-guide-bubble">
                <span aria-hidden="true"></span>
                <p>{{ $secretaryMessage }}</p>
            </div>
        </div>

        @if($pendingSurveyAssignment)
            <section class="mt-6 rounded-3xl border border-violet-200 bg-violet-50 p-5 shadow-sm">
                <p class="text-xs font-semibold tracking-wide text-violet-700">ご協力のお願い</p>
                <div class="mt-1 flex flex-wrap items-center justify-between gap-3">
                    <div><h2 class="text-lg font-bold text-indigo-950">{{ $pendingSurveyAssignment->definition->title }}</h2><p class="mt-1 text-sm text-stone-600">期限：{{ $pendingSurveyAssignment->due_on->format('n月j日') }}　途中保存できます。</p></div>
                    <a href="{{ route('surveys.edit', $pendingSurveyAssignment) }}" class="primary-action">回答する</a>
                </div>
            </section>
        @endif

        @if($todayTreatments->isNotEmpty())
            @php
                $todayTreatmentTypes = ['consultation' => '診察', 'chemotherapy' => '抗がん剤治療', 'infusion' => '点滴', 'injection' => '注射', 'radiation' => '放射線治療', 'procedure' => '処置・手術', 'other' => 'その他'];
                $todayTreatmentStatuses = ['scheduled' => '予定', 'completed' => '受診済み', 'postponed' => '延期', 'changed' => '内容変更'];
            @endphp
            <section class="mt-8 rounded-3xl border border-pink-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="section-today-treatment">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold tracking-wide text-stone-500">今日の予定</p>
                        <h2 id="section-today-treatment" class="mt-1 text-xl font-bold text-stone-800">診察・治療</h2>
                    </div>
                    <a href="{{ route('treatments.index') }}" class="text-sm font-semibold text-pink-700">予定を確認 →</a>
                </div>
                <div class="mt-4 space-y-3">
                    @foreach($todayTreatments as $treatment)
                        <article class="rounded-2xl border border-pink-100 bg-pink-50/50 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <p class="text-xs font-semibold text-pink-700">
                                        @if($treatment->scheduled_at){{ substr($treatment->scheduled_at, 0, 5) }}・@endif
                                        {{ $todayTreatmentTypes[$treatment->treatment_type] ?? '予定' }}
                                    </p>
                                    <h3 class="mt-1 font-semibold text-stone-800">{{ $treatment->name }}</h3>
                                    @if($treatment->hospital || $treatment->department)
                                        <p class="mt-1 text-xs text-stone-500">{{ collect([$treatment->hospital, $treatment->department])->filter()->join('・') }}</p>
                                    @endif
                                    @if($treatment->note)<p class="mt-2 text-sm text-stone-600">相談メモ：{{ $treatment->note }}</p>@endif
                                </div>
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-stone-600">{{ $todayTreatmentStatuses[$treatment->status] ?? $treatment->status }}</span>
                            </div>
                            <details class="mt-3">
                                <summary class="cursor-pointer text-sm font-semibold text-pink-700">診察後のメモを書く</summary>
                                <form method="POST" action="{{ route('treatments.summary', $treatment) }}" class="mt-3">
                                    @csrf @method('PATCH')
                                    <textarea class="form-input" name="visit_summary" rows="3" maxlength="4000" placeholder="説明されたこと、検査結果、薬の変更など">{{ $treatment->visit_summary }}</textarea>
                                    <button class="secondary-action mt-2" type="submit">保存</button>
                                </form>
                            </details>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($todayGoals->isNotEmpty())
            @php
                $goalCategoryLabels = ['exercise' => '運動', 'nutrition' => '食事・水分', 'other' => '暮らし'];
                $goalTimeLabels = ['anytime' => 'いつでも', 'morning' => '朝', 'noon' => '昼', 'evening' => '夜', 'bedtime' => '就寝前'];
                $goalStatusLabels = ['completed' => 'できた', 'partial' => '少しできた', 'rest' => '今日は休む'];
            @endphp
            <section class="mt-8 rounded-3xl border border-violet-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="section-life-goals">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold tracking-wide text-stone-500">無理なく続ける</p>
                        <h2 id="section-life-goals" class="mt-1 text-xl font-bold text-stone-800">今日の目標</h2>
                    </div>
                    <a href="{{ route('goals.index') }}" class="text-sm font-semibold text-stone-600 hover:text-stone-900">目標を整える →</a>
                </div>

                @if (session('status') === 'goal-entry-updated')
                    <p class="mt-4 rounded-2xl bg-amber-50 px-4 py-3 text-sm font-medium text-stone-700">今日の歩みを記録しました。</p>
                @endif

                <div class="mt-5 space-y-3">
                    @foreach ($todayGoals as $goal)
                        @php
                            $goalEntry = $goal->entries->first();
                        @endphp
                        <article class="rounded-2xl border border-stone-200 bg-stone-50/70 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2 text-xs font-medium text-stone-500">
                                        <span>{{ $goalCategoryLabels[$goal->category] ?? '暮らし' }}</span>
                                        <span>・</span>
                                        <span>{{ $goalTimeLabels[$goal->time_of_day] ?? 'いつでも' }}</span>
                                        @if($goal->project)<span>・{{ $goal->project->name }}</span>@endif
                                    </div>
                                    <h3 class="mt-1 font-semibold text-stone-800">{{ $goal->title }}</h3>
                                    @if($goal->target_amount && $goal->target_unit)
                                        <p class="mt-1 text-xs text-stone-500">目安 {{ rtrim(rtrim($goal->target_amount, '0'), '.') }}{{ $goal->target_unit }}</p>
                                    @endif
                                </div>
                                @if($goalEntry)
                                    <span class="rounded-full bg-violet-100 px-3 py-1 text-xs font-semibold text-violet-800">
                                        {{ $goalStatusLabels[$goalEntry->status] }}
                                    </span>
                                @endif
                            </div>

                            @if($goal->category === 'exercise')
                                <form method="POST" action="{{ route('goal-entries.update', $goal) }}" class="mt-4 rounded-2xl border border-violet-100 bg-white p-3">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="recorded_on" value="{{ $today->toDateString() }}">
                                    <div class="grid gap-3 sm:grid-cols-[minmax(0,12rem)_1fr]">
                                        <label class="text-xs font-semibold text-stone-600">
                                            実際にできた量
                                            <span class="mt-1 flex items-center gap-2">
                                                <input type="number" name="actual_amount" value="{{ $goalEntry?->actual_amount }}" min="0" max="999999.99" step="0.1" class="w-full rounded-xl border-stone-200 text-sm focus:border-violet-300 focus:ring-violet-200">
                                                @if($goal->target_unit)
                                                    <span class="shrink-0 font-medium text-stone-500">{{ $goal->target_unit }}</span>
                                                @endif
                                            </span>
                                        </label>
                                        <label class="text-xs font-semibold text-stone-600">
                                            ひとことメモ
                                            <input type="text" name="note" value="{{ $goalEntry?->note }}" maxlength="1000" placeholder="例：ゆっくり歩けた" class="mt-1 w-full rounded-xl border-stone-200 text-sm focus:border-violet-300 focus:ring-violet-200">
                                        </label>
                                    </div>
                                    <div class="mt-3 grid grid-cols-3 gap-2">
                                        @foreach(['completed' => 'できた', 'partial' => '少しできた', 'rest' => '今日は休む'] as $goalStatus => $goalStatusLabel)
                                            <button type="submit" name="status" value="{{ $goalStatus }}" class="rounded-xl border px-2 py-2.5 text-xs font-semibold transition sm:text-sm {{ $goalEntry?->status === $goalStatus ? 'border-violet-300 bg-violet-100 text-stone-800' : 'border-stone-200 bg-white text-stone-600 hover:border-violet-300' }}">{{ $goalStatusLabel }}</button>
                                        @endforeach
                                    </div>
                                </form>
                            @elseif($goal->category === 'nutrition')
                                <form method="POST" action="{{ route('goal-entries.update', $goal) }}" class="mt-4 rounded-2xl border border-amber-100 bg-white p-3">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="recorded_on" value="{{ $today->toDateString() }}">
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <label class="text-xs font-semibold text-stone-600">
                                            食事・水分の区分
                                            <select name="nutrition_type" class="mt-1 w-full rounded-xl border-stone-200 text-sm focus:border-amber-300 focus:ring-amber-200">
                                                <option value="">選択してください</option>
                                                @foreach(['breakfast' => '朝食', 'lunch' => '昼食', 'dinner' => '夕食', 'snack' => '間食', 'hydration' => '水分', 'other' => 'その他'] as $nutritionType => $nutritionLabel)
                                                    <option value="{{ $nutritionType }}" @selected($goalEntry?->nutrition_type === $nutritionType)>{{ $nutritionLabel }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                        <label class="text-xs font-semibold text-stone-600">
                                            食べたもの・飲んだもの
                                            <input type="text" name="food_details" value="{{ $goalEntry?->food_details }}" maxlength="1000" placeholder="例：おにぎり、みそ汁" class="mt-1 w-full rounded-xl border-stone-200 text-sm focus:border-amber-300 focus:ring-amber-200">
                                        </label>
                                    </div>
                                    <label class="mt-3 block text-xs font-semibold text-stone-600">
                                        量（必要なときだけ）
                                        <span class="mt-1 flex max-w-xs items-center gap-2">
                                            <input type="number" name="actual_amount" value="{{ $goalEntry?->actual_amount }}" min="0" max="999999.99" step="0.1" class="w-full rounded-xl border-stone-200 text-sm focus:border-amber-300 focus:ring-amber-200">
                                            @if($goal->target_unit)
                                                <span class="shrink-0 font-medium text-stone-500">{{ $goal->target_unit }}</span>
                                            @endif
                                        </span>
                                    </label>
                                    <div class="mt-3 grid grid-cols-3 gap-2">
                                        @foreach(['completed' => '記録できた', 'partial' => '少し記録', 'rest' => '今日は休む'] as $goalStatus => $goalStatusLabel)
                                            <button type="submit" name="status" value="{{ $goalStatus }}" class="rounded-xl border px-2 py-2.5 text-xs font-semibold transition sm:text-sm {{ $goalEntry?->status === $goalStatus ? 'border-amber-300 bg-amber-100 text-stone-800' : 'border-stone-200 bg-white text-stone-600 hover:border-amber-300' }}">{{ $goalStatusLabel }}</button>
                                        @endforeach
                                    </div>
                                </form>
                            @else
                                <div class="mt-4 grid grid-cols-3 gap-2">
                                    @foreach(['completed' => 'できた', 'partial' => '少しできた', 'rest' => '今日は休む'] as $goalStatus => $goalStatusLabel)
                                        <form method="POST" action="{{ route('goal-entries.update', $goal) }}">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="recorded_on" value="{{ $today->toDateString() }}">
                                            <input type="hidden" name="status" value="{{ $goalStatus }}">
                                            <button type="submit" class="w-full rounded-xl border px-2 py-2.5 text-xs font-semibold transition sm:text-sm {{ $goalEntry?->status === $goalStatus ? 'border-violet-300 bg-violet-100 text-stone-800' : 'border-stone-200 bg-white text-stone-600 hover:border-violet-300' }}">{{ $goalStatusLabel }}</button>
                                        </form>
                                    @endforeach
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
                <p class="mt-4 text-center text-xs text-stone-500">その日の調子に合わせて選べます。あとから押し直しても大丈夫です。</p>
            </section>
        @endif
        @if ($todayProjects->isNotEmpty())
            @php
                $timingLabels = ['once' => 'できた', 'morning' => '朝', 'noon' => '昼', 'evening' => '夜', 'bedtime' => '就寝前'];
                $projectProgress = $todayProjects->mapWithKeys(function ($project) {
                    $total = $project->checkinItems->sum(fn ($item) => $item->kind === 'medication' ? count($item->medication_timings ?? []) : 1);
                    $completed = $project->checkinItems->sum(fn ($item) => $item->kind === 'medication'
                        ? $item->entries->where('status', 'taken')->count()
                        : $item->entries->count());

                    return [$project->id => ['total' => $total, 'completed' => $completed]];
                });
                $allCheckinTotal = $projectProgress->sum('total');
                $allCheckinCompleted = $projectProgress->sum('completed');
            @endphp
            <section
                class="checkin-today-section mt-8"
                aria-labelledby="section-checkins"
                x-data="{ selectedProject: {{ $todayProjects->first()?->id ?? 'null' }} }"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="today-section__heading">
                        <span class="today-section__mark today-section__mark--completed" aria-hidden="true">花</span>
                        <div>
                            <p class="today-section__eyebrow">お薬や毎日の習慣</p>
                            <h2 id="section-checkins" class="today-section__title">今日のチェック</h2>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-1">
                        <span class="checkin-overall-progress">{{ $allCheckinCompleted }}件できた／全{{ $allCheckinTotal }}件</span>
                        <a href="{{ route('care.index') }}" class="text-xs font-semibold text-violet-700">お薬の登録・変更 →</a>
                    </div>
                </div>

                <p class="mt-5 text-xs font-semibold text-stone-500">確認する項目を選ぶ</p>
                <div class="checkin-project-tabs mt-2" role="tablist" aria-label="確認する項目を選ぶ">
                    @foreach($todayProjects as $checkinProject)
                        @php
                            $progress = $projectProgress[$checkinProject->id];
                            $projectCompleted = $progress['total'] > 0 && $progress['completed'] >= $progress['total'];
                        @endphp
                        <button
                            type="button"
                            class="checkin-project-tab"
                            :class="{ 'checkin-project-tab--active': selectedProject === {{ $checkinProject->id }} }"
                            @click="selectedProject = selectedProject === {{ $checkinProject->id }} ? null : {{ $checkinProject->id }}"
                            :aria-selected="selectedProject === {{ $checkinProject->id }}"
                            role="tab"
                        >
                            <span>{{ $checkinProject->name }}</span>
                            @if($progress['completed'] > 0)
                                <span class="checkin-project-tab__flowers" aria-label="今日できた数 {{ $progress['completed'] }}">
                                    @for($flowerIndex = 0; $flowerIndex < $progress['completed']; $flowerIndex++)
                                        <x-checkin-flower class="checkin-project-tab__flower" />
                                    @endfor
                                </span>
                            @endif
                        </button>
                    @endforeach
                </div>

                <div class="mt-5">
                    @foreach ($todayProjects as $checkinProject)
                        @php
                            $progress = $projectProgress[$checkinProject->id];
                        @endphp
                        <div
                            x-show="selectedProject === {{ $checkinProject->id }}"
                            x-cloak
                            role="tabpanel"
                            class="checkin-project-panel"
                        >
                            @if($checkinProject->checkinItems->isNotEmpty())
                                <div class="project-today-group__heading mb-2"><h3>お薬・習慣の確認</h3></div>
                            @endif
                            <div class="space-y-2">
                                @foreach ($checkinProject->checkinItems as $item)
                                    @php
                                        $timings = $item->kind === 'medication' ? ($item->medication_timings ?? []) : ['once'];
                                        $entriesByTiming = $item->entries->keyBy('timing');
                                    @endphp
                                    <div class="checkin-item-block {{ $item->kind === 'medication' ? 'medication-today-card' : '' }}" @if($item->kind === 'medication') x-data="{ open: {{ $entriesByTiming->count() < count($timings) ? 'true' : 'false' }} }" @endif>
                                        @if($item->kind === 'medication')
                                            <button
                                                type="button"
                                                class="medication-accordion-button"
                                                @click="open = ! open"
                                                :aria-expanded="open"
                                            >
                                                <span class="flex min-w-0 items-center gap-2">
                                                    <span class="medication-today-card__name truncate">{{ $item->title }}</span>
                                                    <span class="medication-badge">お薬</span>
                                                </span>
                                                <span class="flex shrink-0 items-center gap-2">
                                                    <span class="text-xs font-semibold text-stone-500">{{ $entriesByTiming->count() }}/{{ count($timings) }} 記録</span>
                                                    <span class="medication-accordion-chevron" :class="{ 'medication-accordion-chevron--open': open }" aria-hidden="true">⌄</span>
                                                </span>
                                            </button>
                                            @if($item->dose_amount || $item->medication_instructions || $item->medication_precautions)
                                                <div class="medication-today-card__details">
                                                    @if($item->dose_amount)<p><strong>1回の量</strong> {{ rtrim(rtrim($item->dose_amount, '0'), '.') }}{{ $item->dose_unit }}</p>@endif
                                                    @if($item->medication_instructions)<p><strong>服用方法</strong> {{ $item->medication_instructions }}</p>@endif
                                                    @if($item->medication_precautions)<p class="text-amber-800">注意：{{ $item->medication_precautions }}</p>@endif
                                                </div>
                                            @endif
                                        @else
                                            <div class="checkin-item-block__title">
                                                <span>{{ $item->title }}</span>
                                            </div>
                                        @endif

                                        <div
                                            class="grid gap-2 sm:grid-cols-2"
                                            @if($item->kind === 'medication') x-show="open" x-cloak x-transition.origin.top @endif
                                        >
                                            @foreach($timings as $timing)
                                                @php($entry = $entriesByTiming->get($timing))
                                                @if($item->kind === 'medication')
                                                    @php($statusLabels = ['taken' => '服用済み', 'missed' => '飲み忘れ', 'skipped' => '服用しなかった', 'later' => 'あとで確認'])
                                                    <div class="medication-time-slot {{ $entry ? 'medication-time-slot--'.$entry->status : '' }}">
                                                        <div class="flex items-center justify-between gap-2">
                                                            <span class="medication-time-slot__label">{{ $timingLabels[$timing] }}</span>
                                                            @if($entry)<span class="medication-current-status medication-current-status--{{ $entry->status }}">{{ $statusLabels[$entry->status] ?? $entry->status }}</span>@else<span class="text-xs font-medium text-stone-400">未記録</span>@endif
                                                        </div>
                                                        <div class="mt-2 grid grid-cols-2 gap-2">
                                                            @foreach($statusLabels as $statusValue => $statusLabel)
                                                                <form method="POST" action="{{ route('checkin-entries.update', $item) }}">
                                                                    @csrf @method('PUT')
                                                                    <input type="hidden" name="checked_on" value="{{ $today->toDateString() }}">
                                                                    <input type="hidden" name="timing" value="{{ $timing }}">
                                                                    <input type="hidden" name="checked" value="1">
                                                                    <input type="hidden" name="status" value="{{ $statusValue }}">
                                                                    <button type="submit" aria-pressed="{{ $entry?->status === $statusValue ? 'true' : 'false' }}" class="medication-status-button medication-status-button--{{ $statusValue }} {{ $entry?->status === $statusValue ? 'medication-status-button--selected' : '' }}">{{ $statusLabel }}</button>
                                                                </form>
                                                            @endforeach
                                                        </div>
                                                        @if($entry)
                                                            <form method="POST" action="{{ route('checkin-entries.update', $item) }}" class="mt-2 text-right">
                                                                @csrf @method('PUT')
                                                                <input type="hidden" name="checked_on" value="{{ $today->toDateString() }}">
                                                                <input type="hidden" name="timing" value="{{ $timing }}">
                                                                <input type="hidden" name="checked" value="0">
                                                                <button type="submit" class="text-xs font-medium text-stone-500">記録を取り消す</button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                @else
                                                    @php($checked = (bool) $entry)
                                                    <form method="POST" action="{{ route('checkin-entries.update', $item) }}">
                                                        @csrf @method('PUT')
                                                        <input type="hidden" name="checked_on" value="{{ $today->toDateString() }}">
                                                        <input type="hidden" name="timing" value="{{ $timing }}">
                                                        <input type="hidden" name="checked" value="{{ $checked ? 0 : 1 }}">
                                                        <button class="checkin-toggle {{ $checked ? 'checkin-toggle--done' : '' }}" type="submit">
                                                            @if($checked)<x-checkin-flower />@else<span class="checkin-toggle__empty" aria-hidden="true"></span>@endif
                                                            <span>{{ $timingLabels[$timing] }}</span>
                                                        </button>
                                                    </form>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if($checkinProject->uses_todos)
                                <div class="project-today-group">
                                    <div class="project-today-group__heading">
                                        <h3>今日のやること</h3>
                                        <a href="{{ route('todos.create',['project_id'=>$checkinProject->id,'due_date'=>$today->toDateString()]) }}">＋ 追加</a>
                                    </div>
                                    @if($checkinProject->todos->isEmpty())
                                        <p class="project-today-empty">今日のやることはありません。</p>
                                    @else
                                        <ul class="mt-2 space-y-2">
                                            @foreach($checkinProject->todos as $projectTodo)
                                                <li class="today-item {{ $projectTodo->is_completed ? 'today-item--done' : '' }}">
                                                    <div class="min-w-0 flex-1">
                                                        <p class="today-item__title {{ $projectTodo->is_completed ? 'line-through decoration-indigo-300/70' : '' }}">{{ $projectTodo->title }}</p>
                                                        @if($formatTime($projectTodo->due_time))<p class="today-item__meta mt-1"><span>{{ $formatTime($projectTodo->due_time) }}</span></p>@endif
                                                    </div>
                                                    @if(!$projectTodo->is_completed)
                                                        <form method="POST" action="{{ route('todos.complete',$projectTodo) }}">@csrf @method('PATCH')<button type="submit" class="today-done-btn">できた</button></form>
                                                    @else
                                                        <span class="project-today-done">できました</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @endif

                            @if($checkinProject->uses_activity_logs)
                                @php($conditionLabels=['good'=>['よい','😊'],'usual'=>['ふつう','🙂'],'hard'=>['つらい','😣']])
                                <div class="project-today-group">
                                    <div class="project-today-group__heading">
                                        <h3>今日の体調・記録</h3>
                                        <a href="{{ route('activity-logs.create',['project_id'=>$checkinProject->id,'performed_on'=>$today->toDateString()]) }}">＋ 記録</a>
                                    </div>
                                    @if($checkinProject->activityLogs->isEmpty())
                                        <p class="project-today-empty">今日の記録はまだありません。</p>
                                    @else
                                        <ul class="mt-2 space-y-2">
                                            @foreach($checkinProject->activityLogs as $projectLog)
                                                <li>
                                                    <a class="today-item" href="{{ route('activity-logs.show',$projectLog) }}">
                                                        <span class="min-w-0 flex-1"><span class="today-item__title">{{ $projectLog->title }}</span></span>
                                                        @if($projectLog->condition&&isset($conditionLabels[$projectLog->condition]))<span class="condition-mini">{{ $conditionLabels[$projectLog->condition][1] }} {{ $conditionLabels[$projectLog->condition][0] }}</span>@endif
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @endif

                            @if($progress['total'] > 0 && $progress['completed'] >= $progress['total'])
                                <div class="checkin-complete-message">
                                    <x-checkin-flower />
                                    <p><strong>今日のチェックをすべて記録しました。</strong><br>無理なく続けていきましょう。</p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>


            </section>
        @endif

        <div class="mt-10 flex items-center gap-3"><span class="h-px flex-1 bg-stone-200"></span><p class="text-xs font-semibold text-stone-500">予定と記録</p><span class="h-px flex-1 bg-stone-200"></span></div>

        <div class="mt-5 space-y-5">
            <section class="today-section today-section--overdue {{ $overdueTodos->isEmpty() ? 'today-section--empty' : '' }}" aria-labelledby="section-overdue">
                <div class="today-section__heading">
                    <span class="today-section__mark today-section__mark--overdue" aria-hidden="true">!</span>
                    <div>
                        <p class="today-section__eyebrow">最初に確認</p>
                        <h2 id="section-overdue" class="today-section__title">期限を過ぎていること</h2>
                    </div>
                </div>
                @if ($overdueTodos->isEmpty())
                    <p class="today-section__empty">期限を過ぎているものはありません。安心してくださいね。</p>
                @else
                    <ul class="mt-4 space-y-2">
                        @foreach ($overdueTodos as $todo)
                            <li class="today-item today-item--overdue">
                                <div class="min-w-0 flex-1">
                                    <p class="today-item__title">{{ $todo->title }}</p>
                                    <div class="today-item__meta">
                                        <span>期限 {{ $todo->due_date->format('n/j') }}</span>
                                        @if ($formatTime($todo->due_time))<span>{{ $formatTime($todo->due_time) }}</span>@endif
                                        <span class="today-priority today-priority--{{ $todo->priority }}">優先度 {{ $priorityLabels[$todo->priority] ?? $todo->priority }}</span>
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('todos.complete', $todo) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="today-done-btn">できた</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="today-section today-section--today {{ $todayDueTodos->isEmpty() ? 'today-section--empty' : '' }}" aria-labelledby="section-today">
                <div class="today-section__heading">
                    <span class="today-section__mark today-section__mark--today" aria-hidden="true">○</span>
                    <div>
                        <p class="today-section__eyebrow">今日の予定</p>
                        <h2 id="section-today" class="today-section__title">今日やること</h2>
                    </div>
                </div>
                @if ($todayDueTodos->isEmpty())
                    <p class="today-section__empty">今日のやることはまだありません。</p>
                @else
                    <ul class="mt-4 space-y-2">
                        @foreach ($todayDueTodos as $todo)
                            <li class="today-item">
                                <div class="min-w-0 flex-1">
                                    <p class="today-item__title">{{ $todo->title }}</p>
                                    <div class="today-item__meta">
                                        @if ($formatTime($todo->due_time))<span>{{ $formatTime($todo->due_time) }}</span>@endif
                                        <span class="today-priority today-priority--{{ $todo->priority }}">優先度 {{ $priorityLabels[$todo->priority] ?? $todo->priority }}</span>
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('todos.complete', $todo) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="today-done-btn">できた</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
                <a href="{{ route('todos.create') }}" class="today-section__action">＋ 今日のやることを追加</a>
            </section>

            <section class="today-section today-section--completed {{ $completedTodayTodos->isEmpty() ? 'today-section--empty' : '' }}" aria-labelledby="section-completed">
                <div class="today-section__heading">
                    <span class="today-section__mark today-section__mark--completed" aria-hidden="true">✓</span>
                    <div>
                        <p class="today-section__eyebrow">今日の歩み</p>
                        <h2 id="section-completed" class="today-section__title">今日できたこと</h2>
                    </div>
                </div>
                @if ($completedTodayTodos->isEmpty())
                    <p class="today-section__empty">できたことは、ここに少しずつ増えていきます。</p>
                @else
                    <ul class="mt-4 space-y-2">
                        @foreach ($completedTodayTodos as $todo)
                            <li class="today-item today-item--done">
                                <div class="min-w-0 flex-1">
                                    <p class="today-item__title line-through decoration-indigo-300/70">{{ $todo->title }}</p>
                                    <div class="today-item__meta">
                                        @if ($todo->completed_at)<span>{{ $todo->completed_at->format('H:i') }} に完了</span>@endif
                                        <span class="text-indigo-600">できました</span>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
                <a href="{{ route('todos.index') }}" class="today-section__action">やることを整理する →</a>
            </section>
        </div>
    </main>

    <x-mobile-navigation />
    <footer class="mt-8 app-footer border-t py-6 text-center text-xs text-stone-500">
        © {{ date('Y') }} ANATANOHISHO
    </footer>
</body>
</html>






