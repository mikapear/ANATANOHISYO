<x-site-app title="カレンダー | ANATANOHISHO">
    @php
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $conditionLabels = ['good' => ['よい', '😊'], 'usual' => ['ふつう', '🙂'], 'hard' => ['つらい', '😣']];
        $treatmentStatusLabels = ['scheduled' => '予定', 'completed' => '実施', 'postponed' => '延期', 'cancelled' => '中止', 'changed' => '内容変更'];
        $treatmentStatusClasses = ['scheduled' => 'bg-stone-100 text-stone-700', 'completed' => 'bg-emerald-100 text-emerald-800', 'postponed' => 'bg-amber-100 text-amber-800', 'cancelled' => 'bg-stone-200 text-stone-500', 'changed' => 'bg-violet-100 text-violet-800'];
    @endphp

    <div class="flex items-center justify-between gap-4">
        <a class="calendar-move" href="{{ route('calendar.index', ['month' => $current->copy()->subMonth()->format('Y-m'), 'project_id' => $projectId]) }}" aria-label="前の月">‹</a>
        <div class="text-center">
            <p class="text-[11px] font-bold tracking-[0.18em] text-violet-600">CALENDAR</p>
            <h1 class="text-2xl font-bold text-indigo-950">{{ $current->format('Y年n月') }}</h1>
        </div>
        <a class="calendar-move" href="{{ route('calendar.index', ['month' => $current->copy()->addMonth()->format('Y-m'), 'project_id' => $projectId]) }}" aria-label="次の月">›</a>
    </div>

    <div class="mt-5 overflow-hidden rounded-xl border border-indigo-100 bg-white shadow-sm">
        <div class="grid grid-cols-7 border-b border-indigo-100 bg-indigo-50/60">
            @foreach ($weekdays as $i => $name)
                <div class="py-2 text-center text-xs font-semibold {{ $i === 0 ? 'text-red-500' : ($i === 6 ? 'text-blue-600' : 'text-indigo-800') }}">{{ $name }}</div>
            @endforeach
        </div>
        <div class="grid grid-cols-7">
            @foreach ($days as $day)
                @php
                    $key = $day->toDateString();
                    $dayTodos = $todos->get($key, collect());
                    $dayLogs = $logs->get($key, collect());
                    $dayTreatments = $treatments->get($key, collect());
                    $dayGoals = $lifeGoals->filter(fn ($goal) => $goal->isScheduledFor($day));
                    $dayGoalEntries = $lifeGoalEntries->get($key, collect());
                    $dayRecordedGoalCount = $dayGoalEntries->whereIn('life_goal_id', $dayGoals->pluck('id'))->count();
                    $scheduledDayItems = $checkinItems->filter(fn ($item) => $item->isScheduledFor($day));
                    $scheduledDayItemIds = $scheduledDayItems->pluck('id');
                    $dayCheckins = $checkinEntries->get($key, collect())
                        ->filter(fn ($entry) => $scheduledDayItemIds->contains($entry->checkin_item_id));
                    $dayMedicationItems = $scheduledDayItems->where('kind', 'medication');
                    $dayMedicationItemIds = $dayMedicationItems->pluck('id');
                    $dayMedicationTotal = $dayMedicationItems->sum(fn ($item) => $item->scheduledSlotCount());
                    $dayMedicationEntries = $dayCheckins->whereIn('checkin_item_id', $dayMedicationItemIds);
                    $dayMedicationTaken = $dayMedicationEntries->where('status', 'taken')->count();
                    $dayMedicationState = $dayMedicationTotal > 0
                        ? ($dayMedicationTaken >= $dayMedicationTotal
                            ? 'done'
                            : ($dayMedicationEntries->count() >= $dayMedicationTotal ? 'attention' : 'pending'))
                        : null;
                    $dayVisibleTreatments = $dayTreatments->where('status', '!=', 'cancelled');
                    $dayAsNeededUsages = $asNeededUsages->get($key, collect());
                    $isCurrent = $day->month === $current->month;
                    $isSelected = $selected?->isSameDay($day);
                    $isToday = $day->isToday();
                @endphp
                <a href="{{ route('calendar.index', ['month' => $current->format('Y-m'), 'date' => $key, 'project_id' => $projectId]) }}" class="calendar-day {{ ! $isCurrent ? 'calendar-day--outside' : '' }} {{ $isSelected ? 'calendar-day--selected' : '' }}">
                    <span class="calendar-day__number {{ $isToday ? 'calendar-day__number--today' : '' }}">{{ $day->day }}</span>
                    <div class="mt-1 space-y-1">
                        <span class="calendar-signals">
                            @if($dayMedicationState)
                                <span class="calendar-signal calendar-signal--medication calendar-signal--{{ $dayMedicationState }}" title="お薬：{{ $dayMedicationState === 'done' ? 'すべて服用済み' : ($dayMedicationState === 'attention' ? '飲み忘れ・服用しなかった記録あり' : $dayMedicationTaken.'/'.$dayMedicationTotal.'服用済み') }}" aria-label="お薬：{{ $dayMedicationState === 'done' ? 'すべて服用済み' : ($dayMedicationState === 'attention' ? '確認が必要' : 'まだ') }}">
                                    <span>薬</span><small>{{ $dayMedicationState === 'done' ? '✓' : ($dayMedicationState === 'attention' ? '!' : $dayMedicationTaken.'/'.$dayMedicationTotal) }}</small>
                                </span>
                            @endif
                            @if($dayGoals->isNotEmpty())
                                <span class="calendar-signal calendar-signal--goal calendar-signal--{{ $dayRecordedGoalCount >= $dayGoals->count() ? 'done' : 'pending' }}" title="目標：{{ $dayRecordedGoalCount >= $dayGoals->count() ? '記録済み' : $dayRecordedGoalCount.'/'.$dayGoals->count().'記録済み' }}" aria-label="目標：{{ $dayRecordedGoalCount >= $dayGoals->count() ? '記録済み' : 'まだ' }}">
                                    <span>目</span><small>{{ $dayRecordedGoalCount >= $dayGoals->count() ? '✓' : $dayRecordedGoalCount.'/'.$dayGoals->count() }}</small>
                                </span>
                            @endif
                            @if($dayVisibleTreatments->isNotEmpty())
                                <span class="calendar-signal calendar-signal--treatment" title="診察・治療予定 {{ $dayVisibleTreatments->count() }}件" aria-label="診察・治療予定あり"><span>診</span><small>{{ $dayVisibleTreatments->count() }}</small></span>
                            @endif
                            @if($dayAsNeededUsages->isNotEmpty())
                                <span class="calendar-signal calendar-signal--medication calendar-signal--done" title="頓服 {{ $dayAsNeededUsages->count() }}回" aria-label="頓服の使用記録あり"><span>頓</span><small>{{ $dayAsNeededUsages->count() }}</small></span>
                            @endif
                        </span>
                        @if ($dayTodos->isNotEmpty())<span class="calendar-count calendar-count--todo">予定 {{ $dayTodos->count() }}</span>@endif
                        @if ($dayLogs->isNotEmpty())<span class="calendar-count calendar-count--log">記録 {{ $dayLogs->count() }}</span>@endif
                        @php
                            $dayConditions = $dayLogs->pluck('condition')->filter()->unique()->values();
                        @endphp
                        @if($dayConditions->isNotEmpty())
                            <span class="calendar-condition-row" aria-label="体調記録">
                                @foreach($dayConditions->take(3) as $condition)
                                    @if(isset($conditionLabels[$condition]))<span title="体調：{{ $conditionLabels[$condition][0] }}">{{ $conditionLabels[$condition][1] }}</span>@endif
                                @endforeach
                            </span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    <div class="calendar-legend mt-3" aria-label="カレンダーの見方">
        <span><i class="calendar-legend__mark calendar-legend__mark--done">✓</i>できた・記録済み</span>
        <span><i class="calendar-legend__mark calendar-legend__mark--pending">•</i>まだ・一部</span>
        <span><i class="calendar-legend__mark calendar-legend__mark--treatment">診</i>診察・治療</span>
    </div>

    <div class="mt-3 flex items-center justify-between gap-3 text-xs text-stone-500">
        <p>日付を選ぶと、下に詳しい内容が表示されます。</p>
        <a href="{{ route('calendar.index', ['month' => now()->format('Y-m'), 'date' => now()->toDateString(), 'project_id' => $projectId]) }}" class="shrink-0 font-bold text-violet-700">今日へ</a>
    </div>

    <details class="calendar-filter mt-4" @if($projectId) open @endif>
        <summary>表示する分類を絞り込む</summary>
        <form method="GET" class="mt-3 flex items-center gap-3">
            <input type="hidden" name="month" value="{{ $current->format('Y-m') }}">
            @if($selected)<input type="hidden" name="date" value="{{ $selected->toDateString() }}">@endif
            <select id="calendar-project" name="project_id" class="form-input" aria-label="表示する分類" onchange="this.form.submit()">
                <option value="">すべてまとめて表示</option>
                @foreach($projects as $filterProject)
                    <option value="{{ $filterProject->id }}" @selected($projectId===$filterProject->id)>{{ $filterProject->name }}</option>
                @endforeach
            </select>
            <a href="{{ route('projects.index') }}" class="shrink-0 font-semibold text-violet-700">予定を管理</a>
        </form>
    </details>

    @if ($selected)
        @php
            $selectedKey = $selected->toDateString();
            $selectedTodos = $todos->get($selectedKey, collect());
            $selectedLogs = $logs->get($selectedKey, collect());
            $selectedTreatments = $treatments->get($selectedKey, collect());
            $selectedAsNeededUsages = $asNeededUsages->get($selectedKey, collect());
            $selectedCheckins = $checkinEntries->get($selectedKey, collect());
            $selectedGoals = $lifeGoals->filter(fn ($goal) => $goal->isScheduledFor($selected));
            $selectedGoalEntries = $lifeGoalEntries->get($selectedKey, collect())->keyBy('life_goal_id');
            $selectedMedicationItems = $checkinItems->filter(fn ($item) => $item->kind === 'medication' && $item->isScheduledFor($selected));
            $selectedMedicationTotal = $selectedMedicationItems->sum(fn ($item) => $item->scheduledSlotCount());
            $selectedMedicationEntries = $selectedCheckins->whereIn('checkin_item_id', $selectedMedicationItems->pluck('id'));
            $selectedMedicationTaken = $selectedMedicationEntries->where('status', 'taken')->count();
            $selectedRecordedGoals = $selectedGoalEntries->whereIn('life_goal_id', $selectedGoals->pluck('id'))->count();
            $goalStatusLabels = ['completed' => 'できた', 'partial' => '少しできた', 'rest' => '今日は休む'];
        @endphp
        <section class="today-section mt-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="today-section__title">{{ $selected->format('n月j日') }}（{{ $weekdays[$selected->dayOfWeek] }}）</h2>
                <div class="flex flex-wrap gap-2">
                    <a class="text-action" href="{{ route('todos.create', ['due_date' => $selectedKey, 'project_id' => $projectId]) }}">＋ やること</a>
                    <a class="text-action" href="{{ route('activity-logs.create', ['performed_on' => $selectedKey, 'project_id' => $projectId]) }}">＋ 記録</a>
                    <a class="text-action" href="{{ route('treatments.index', ['scheduled_on' => $selectedKey]) }}">＋ 診察予定</a>
                </div>
            </div>
            @if($selectedMedicationTotal > 0 || $selectedGoals->isNotEmpty() || $selectedTreatments->isNotEmpty())
                <div class="calendar-day-overview mt-5">
                    @if($selectedMedicationTotal > 0)
                        <div class="calendar-overview-card calendar-overview-card--{{ $selectedMedicationTaken >= $selectedMedicationTotal ? 'done' : 'pending' }}">
                            <span class="calendar-overview-card__icon">薬</span>
                            <span><strong>お薬</strong><small>{{ $selectedMedicationTaken >= $selectedMedicationTotal ? 'すべて服用できています' : $selectedMedicationTaken.'／'.$selectedMedicationTotal.' 服用済み' }}</small></span>
                        </div>
                    @endif
                    @if($selectedGoals->isNotEmpty())
                        <div class="calendar-overview-card calendar-overview-card--{{ $selectedRecordedGoals >= $selectedGoals->count() ? 'done' : 'pending' }}">
                            <span class="calendar-overview-card__icon">目</span>
                            <span><strong>目標</strong><small>{{ $selectedRecordedGoals >= $selectedGoals->count() ? 'すべて記録済みです' : ($selectedGoals->count() - $selectedRecordedGoals).'件まだ記録していません' }}</small></span>
                        </div>
                    @endif
                    @if($selectedTreatments->where('status', '!=', 'cancelled')->isNotEmpty())
                        <div class="calendar-overview-card calendar-overview-card--treatment">
                            <span class="calendar-overview-card__icon">診</span>
                            <span><strong>診察・治療</strong><small>{{ $selectedTreatments->where('status', '!=', 'cancelled')->count() }}件の予定があります</small></span>
                        </div>
                    @endif
                </div>
            @endif
            @if($selectedAsNeededUsages->isNotEmpty())
                <div class="mt-5 rounded-2xl border border-violet-200 bg-violet-50/60 p-4">
                    <h3 class="text-sm font-semibold text-violet-900">この日に使った頓服</h3>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach($selectedAsNeededUsages as $usage)
                            <span class="rounded-full bg-white px-3 py-2 text-sm font-semibold text-violet-800">{{ $usage->item->title }}・{{ $usage->used_at->format('H:i') }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
            @if($selectedTreatments->isNotEmpty())
                <div class="mt-5 rounded-2xl border border-pink-200 bg-pink-50/60 p-4">
                    <h3 class="text-sm font-semibold text-stone-800">診察・治療予定</h3>
                    <ul class="mt-2 space-y-2">
                        @foreach($selectedTreatments as $treatment)
                            <li class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-white px-3 py-2">
                                <span>
                                    <span class="font-semibold text-stone-800">{{ $treatment->name }}</span>
                                    @if($treatment->cycle_number)<span class="ml-2 text-xs text-stone-500">第{{ $treatment->cycle_number }}クール</span>@endif
                                    <span class="ml-2 rounded-full px-2 py-1 text-[11px] font-semibold {{ $treatmentStatusClasses[$treatment->status] ?? $treatmentStatusClasses['scheduled'] }}">{{ $treatmentStatusLabels[$treatment->status] ?? '予定' }}</span>
                                </span>
                                <span class="flex flex-wrap items-center gap-2 text-xs font-medium text-pink-700">
                                    <span>@if($treatment->scheduled_at){{ substr($treatment->scheduled_at, 0, 5) }} @endif
                                    {{ ['consultation'=>'診察','chemotherapy'=>'抗がん剤','infusion'=>'点滴','injection'=>'注射','radiation'=>'放射線','procedure'=>'処置・手術','other'=>'その他'][$treatment->treatment_type] }}</span>
                                    <a class="rounded-full border border-pink-200 bg-white px-2 py-1 text-stone-600 hover:border-pink-400" href="{{ route('treatments.index') }}#treatment-{{ $treatment->id }}">診察メモ</a>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="mt-5 grid gap-6 sm:grid-cols-2">
                <div>
                    <h3 class="text-sm font-semibold text-indigo-900">やること</h3>
                    @if ($selectedTodos->isEmpty())
                        <p class="mt-2 text-sm text-stone-500">予定はありません。</p>
                    @else
                        <ul class="mt-2 space-y-2">@foreach ($selectedTodos as $todo)<li class="today-item"><span class="{{ $todo->is_completed ? 'line-through text-stone-400' : 'text-indigo-950' }}">{{ $todo->title }}</span>@if ($todo->due_time)<span class="text-xs text-stone-500">{{ substr($todo->due_time, 0, 5) }}</span>@endif</li>@endforeach</ul>
                    @endif
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-indigo-900">体調・活動記録</h3>
                    @if ($selectedLogs->isEmpty())
                        <p class="mt-2 text-sm text-stone-500">記録はありません。</p>
                    @else
                        <ul class="mt-2 space-y-2">
                            @foreach ($selectedLogs as $log)
                                <li><a class="today-item" href="{{ route('activity-logs.show', $log) }}">
                                    <span class="min-w-0 flex-1">
                                        <span class="text-indigo-950">{{ $log->title }}</span>
                                        @if(!empty($log->symptoms))<span class="mt-1 block text-xs text-stone-500">症状 {{ count($log->symptoms) }}件</span>@endif
                                    </span>
                                    @if($log->condition&&isset($conditionLabels[$log->condition]))<span class="condition-mini">{{ $conditionLabels[$log->condition][1] }} {{ $conditionLabels[$log->condition][0] }}</span>@elseif($log->performed_at)<span class="text-xs text-stone-500">{{ substr($log->performed_at,0,5) }}</span>@endif
                                </a></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            @if($selectedGoals->isNotEmpty())
                <div class="mt-6 border-t border-stone-100 pt-5">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold text-indigo-900">生活目標</h3>
                        @if($selected->isToday())
                            <a href="{{ route('dashboard') }}#section-life-goals" class="text-action">今日の目標を記録する →</a>
                        @endif
                    </div>
                    <ul class="mt-3 space-y-2">
                        @foreach($selectedGoals as $selectedGoal)
                            @php
                                $selectedGoalEntry = $selectedGoalEntries->get($selectedGoal->id);
                            @endphp
                            <li class="today-item">
                                <span class="min-w-0 flex-1">
                                    <span class="block font-medium text-indigo-950">{{ $selectedGoal->title }}</span>
                                    @if($selectedGoalEntry?->food_details)
                                        <span class="mt-1 block text-xs text-stone-500">{{ $selectedGoalEntry->food_details }}</span>
                                    @elseif($selectedGoalEntry?->note)
                                        <span class="mt-1 block text-xs text-stone-500">{{ $selectedGoalEntry->note }}</span>
                                    @endif
                                </span>
                                @if($selectedGoalEntry)
                                    <span class="rounded-full bg-violet-100 px-3 py-1 text-xs font-semibold text-violet-800">
                                        {{ $goalStatusLabels[$selectedGoalEntry->status] }}
                                        @if($selectedGoalEntry->actual_amount && $selectedGoal->target_unit)
                                            ・{{ rtrim(rtrim($selectedGoalEntry->actual_amount, '0'), '.') }}{{ $selectedGoal->target_unit }}
                                        @endif
                                    </span>
                                @else
                                    <span class="text-xs font-medium text-stone-400">未記録</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if($checkinItems->isNotEmpty())
                @php
                $timingLabels = ['once' => 'できた', 'morning' => '朝', 'noon' => '昼', 'evening' => '夜', 'bedtime' => '就寝前'];
            @endphp
                <div class="mt-6 border-t border-stone-100 pt-5">
                    <h3 class="text-sm font-semibold text-indigo-900">お薬と今日のチェック</h3>
                    <p class="mt-1 text-xs text-stone-500">お薬は選ぶと自動で記録されます。</p>

                    <div class="mt-3 space-y-4">
                        @foreach($checkinProjects as $checkinProject)
                            <div>
                                <p class="mb-2 text-sm font-semibold text-stone-700">{{ $checkinProject->name }}</p>
                                <div class="calendar-checkin-card-grid">
                                    @foreach($checkinProject->checkinItems->filter(fn ($item) => $item->isScheduledFor($selected)) as $item)
                                        @php
                                            $timings = $item->kind === 'medication' ? ($item->medication_timings ?? []) : ['once'];
                                        @endphp
                                        <div class="checkin-item-block {{ $item->kind === 'medication' ? 'calendar-medication-card' : '' }}">
                                            <div class="checkin-item-block__title">
                                                <span>{{ $item->title }}</span>
                                                @if($item->kind === 'medication')<span class="medication-badge">お薬</span>@endif
                                            </div>
                                            @if($item->kind === 'medication' && ($item->dose_amount || $item->medication_instructions))
                                                <p class="mb-2 text-xs text-stone-500">
                                                    @if($item->dose_amount)1回 {{ rtrim(rtrim($item->dose_amount, '0'), '.') }}{{ $item->dose_unit }}@endif
                                                    @if($item->medication_instructions)・{{ $item->medication_instructions }}@endif
                                                </p>
                                            @endif
                                            <div class="grid gap-2">
                                                @foreach($timings as $timing)
                                                    @php
                                                        $entry = $selectedCheckins->first(fn ($candidate) => $candidate->checkin_item_id === $item->id && $candidate->timing === $timing);
                                                        $checked = (bool) $entry;
                                                    @endphp
                                                    @if($item->kind === 'medication')
                                                        @php($medicationStatusLabels = ['taken' => '服用済み', 'missed' => '飲み忘れ', 'skipped' => '服用しなかった', 'later' => 'あとで確認'])
                                                        <form method="POST" action="{{ route('checkin-entries.update', $item) }}" class="calendar-medication-select">
                                                            @csrf @method('PUT')
                                                            <input type="hidden" name="checked_on" value="{{ $selectedKey }}">
                                                            <input type="hidden" name="timing" value="{{ $timing }}">
                                                            <input type="hidden" name="checked" value="{{ $entry ? 1 : 0 }}">
                                                            <label for="calendar-medication-{{ $item->id }}-{{ $timing }}">{{ $timingLabels[$timing] }}</label>
                                                            <select id="calendar-medication-{{ $item->id }}-{{ $timing }}" name="status" class="form-input" onchange="this.form.querySelector('[name=checked]').value = this.value ? '1' : '0'; this.form.requestSubmit();">
                                                                <option value="" @selected(! $entry)>未記録</option>
                                                                @foreach($medicationStatusLabels as $statusValue => $statusLabel)
                                                                    <option value="{{ $statusValue }}" @selected($entry?->status === $statusValue)>{{ $statusLabel }}</option>
                                                                @endforeach
                                                            </select>
                                                        </form>
                                                    @else
                                                    <form method="POST" action="{{ route('checkin-entries.update', $item) }}" class="calendar-checkin-row">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="checked_on" value="{{ $selectedKey }}">
                                                        <input type="hidden" name="timing" value="{{ $timing }}">
                                                        <input type="hidden" name="checked" value="{{ $checked ? 0 : 1 }}">
                                                        <button class="checkin-icon-button" type="submit" aria-label="{{ $checked ? '花丸を取り消す' : '花丸をつける' }}">
                                                            @if($checked)
                                                                <x-checkin-flower />
                                                            @else
                                                                <span class="checkin-toggle__empty"></span>
                                                            @endif
                                                        </button>
                                                        <span class="min-w-0 flex-1 text-sm font-medium text-indigo-950">{{ $timingLabels[$timing] }}</span>
                                                        @if($entry?->note)<span class="text-xs text-stone-500">{{ $entry->note }}</span>@endif
                                                    </form>
                                                    @if($checked)
                                                        <form method="POST" action="{{ route('checkin-entries.update', $item) }}" class="ml-12 flex gap-2">
                                                            @csrf
                                                            @method('PUT')
                                                            <input type="hidden" name="checked_on" value="{{ $selectedKey }}">
                                                            <input type="hidden" name="timing" value="{{ $timing }}">
                                                            <input type="hidden" name="checked" value="1">
                                                            <input name="note" value="{{ $entry->note }}" maxlength="500" class="form-input" placeholder="ひとことメモ（任意）">
                                                            <button class="secondary-action shrink-0" type="submit">メモ保存</button>
                                                        </form>
                                                    @endif
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
    @else
        <p class="mt-5 text-center text-sm text-stone-500">日付を選ぶと、その日の予定と記録を確認できます。</p>
    @endif

    <aside class="calendar-support mt-6">
        <img src="{{ asset('images/brand/anatanohisyo-guide.png') }}" alt="" class="h-14 w-12 shrink-0 rounded-xl object-cover">
        <div class="min-w-0 flex-1">
            <p>今日の確認や、最近の体調・活動の振り返りはこちらからできます。</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <a href="{{ route('dashboard') }}" class="primary-action inline-flex">今日の詳しい確認へ</a>
                <a href="{{ route('reviews.index') }}" class="secondary-action inline-flex">最近を振り返る</a>
            </div>
        </div>
    </aside>
</x-site-app>



