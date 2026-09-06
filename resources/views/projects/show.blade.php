<x-site-app title="{{ $project->name }} | ANATANOHISHO">
@php
    $statusLabels=['active'=>'進行中','completed'=>'完了','on_hold'=>'保留'];
    $templateLabels=['planning'=>'計画','checkin'=>'習慣・服薬チェック','journal'=>'活動・体調記録'];
@endphp
<div class="flex flex-wrap items-start justify-between gap-4">
    <div>
        <a href="{{ route('projects.index') }}" class="text-sm font-medium text-indigo-700">← 暮らしの予定一覧</a>
        <div class="mt-3 flex flex-wrap items-center gap-3"><h1 class="text-2xl font-bold sm:text-3xl">{{ $project->name }}</h1><span class="project-status project-status--{{ $project->status }}">{{ $statusLabels[$project->status] }}</span></div>
        <p class="mt-2 text-sm text-stone-500">{{ $templateLabels[$project->template] ?? $project->template }}</p>
    </div>
    <div class="flex gap-2"><a href="{{ route('projects.edit',$project) }}" class="secondary-action">編集</a><form method="POST" action="{{ route('projects.destroy',$project) }}" onsubmit="return confirm('暮らしの予定を削除しますか？関連するやることと記録は残ります。')">@csrf @method('DELETE')<button class="secondary-action text-red-600">削除</button></form></div>
</div>
@if($project->description)<p class="mt-5 whitespace-pre-line text-sm leading-relaxed text-stone-600">{{ $project->description }}</p>@endif
<div class="mt-4 flex flex-wrap gap-3 text-sm text-stone-500">@if($project->start_date)<span>開始 {{ $project->start_date->format('Y/n/j') }}</span>@endif @if($project->due_date)<span>期限 {{ $project->due_date->format('Y/n/j') }}</span>@endif</div>

@if($project->uses_checkins)
<section class="checkin-project-section mt-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="today-section__eyebrow">暮らしの予定の設定</p>
            <h2 class="today-section__title">習慣・服薬を登録する</h2>
        </div>
        <a href="{{ route('calendar.index', ['project_id' => $project->id, 'date' => $today->toDateString()]) }}" class="text-action">カレンダーで見る →</a>
    </div>


    @if($project->checkinItems->isEmpty())
        <p class="today-section__empty">チェック項目はまだありません。最初の項目を追加してみましょう。</p>
    @else
        <div class="mt-5 space-y-3">
            @foreach($project->checkinItems as $item)
                <div class="checkin-manage-row {{ ! $item->is_active ? 'checkin-manage-row--inactive' : '' }}">
                    <form method="POST" action="{{ route('checkin-items.update', $item) }}" class="flex min-w-0 flex-1 flex-wrap items-center gap-2" x-data="{ schedule: '{{ $item->schedule_type }}' }">
                        @csrf
                        @method('PATCH')
                        <div class="min-w-44 flex-1">
                            <input name="title" value="{{ $item->title }}" class="form-input" maxlength="100" required>
                            @if($item->kind === 'medication')
                                @php($timingLabels = ['morning' => '朝', 'noon' => '昼', 'evening' => '夜', 'bedtime' => '就寝前'])
                                <div class="mt-1 flex flex-wrap gap-1">
                                    <span class="medication-badge">お薬</span>
                                    @foreach($item->medication_timings ?? [] as $timing)
                                        <span class="medication-timing">{{ $timingLabels[$timing] ?? $timing }}</span>
                                    @endforeach
                                </div>
                            @endif
                            <div class="mt-1 flex flex-wrap gap-1">
                                <span class="schedule-badge">{{ ['daily'=>'毎日','weekdays'=>'曜日指定','cycle'=>'内服・休薬周期'][$item->schedule_type] ?? $item->schedule_type }}</span>
                                @if($item->schedule_type === 'cycle')<span class="schedule-badge">{{ $item->cycle_on_days }}日内服・{{ $item->cycle_rest_days }}日休薬</span>@endif
                                @if($item->starts_on)<span class="schedule-badge">{{ $item->starts_on->format('Y/n/j') }}から</span>@endif
                                @if($item->ends_on)<span class="schedule-badge">{{ $item->ends_on->format('Y/n/j') }}まで</span>@endif
                            </div>
                        </div>
                        <details class="schedule-edit-details order-last w-full">
                            <summary>曜日・期間・周期を変更</summary>
                            <div class="mt-3 grid gap-3 sm:grid-cols-3">
                                <div>
                                    <label class="form-label">予定</label>
                                    <select name="schedule_type" class="form-input" x-model="schedule">
                                        <option value="daily">毎日</option>
                                        <option value="weekdays">曜日を選ぶ</option>
                                        @if($item->kind === 'medication')<option value="cycle">内服・休薬周期</option>@endif
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">開始日</label>
                                    <input type="date" name="starts_on" value="{{ $item->starts_on?->toDateString() }}" class="form-input">
                                </div>
                                <div>
                                    <label class="form-label">終了日</label>
                                    <input type="date" name="ends_on" value="{{ $item->ends_on?->toDateString() }}" class="form-input">
                                </div>
                            </div>
                            <div class="mt-3 grid grid-cols-4 gap-2 sm:grid-cols-7" x-show="schedule === 'weekdays'" x-cloak>
                                @foreach(['日','月','火','水','木','金','土'] as $weekdayValue => $weekdayLabel)
                                    <label class="weekday-option">
                                        <input type="checkbox" name="weekdays[]" value="{{ $weekdayValue }}" @checked(in_array($weekdayValue, $item->weekdays ?? [], true))>
                                        <span>{{ $weekdayLabel }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @if($item->kind === 'medication')
                                <div class="cycle-settings mt-3" x-show="schedule === 'cycle'" x-cloak>
                                    <p class="form-help">開始日を周期の1日目として繰り返します。</p>
                                    <div class="mt-2 grid grid-cols-2 gap-3">
                                        <div><label class="form-label">内服する日数</label><input type="number" name="cycle_on_days" min="1" max="365" value="{{ $item->cycle_on_days }}" class="form-input" placeholder="例：14"></div>
                                        <div><label class="form-label">休薬する日数</label><input type="number" name="cycle_rest_days" min="1" max="365" value="{{ $item->cycle_rest_days }}" class="form-input" placeholder="例：7"></div>
                                    </div>
                                </div>
                            @endif
                        </details>
                        <label class="flex items-center gap-2 text-xs text-stone-500">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" @checked($item->is_active)>
                            表示する
                        </label>
                        <button class="secondary-action" type="submit">保存</button>
                    </form>
                    <form method="POST" action="{{ route('checkin-items.destroy', $item) }}" onsubmit="return confirm('この項目と、これまでの花丸を削除しますか？')">
                        @csrf
                        @method('DELETE')
                        <button class="text-action text-red-600" type="submit">削除</button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('checkin-items.store', $project) }}" class="checkin-add-panel mt-5" x-data="{ kind: '{{ old('kind', 'checkin') }}', schedule: '{{ old('schedule_type', 'daily') }}' }">
        @csrf
        <div class="grid gap-4 sm:grid-cols-[11rem_1fr]">
            <div>
                <label for="new-checkin-kind" class="form-label">登録するもの</label>
                <select id="new-checkin-kind" name="kind" class="form-input" x-model="kind">
                    <option value="checkin">通常のチェック</option>
                    <option value="medication">お薬</option>
                </select>
            </div>
            <div>
                <label for="new-checkin-title" class="form-label" x-text="kind === 'medication' ? '薬の名前' : '項目名'"></label>
                <input id="new-checkin-title" name="title" value="{{ old('title') }}" class="form-input" maxlength="100" :placeholder="kind === 'medication' ? '例：〇〇錠' : '例：ストレッチ'" required>
            </div>
        </div>

        <div class="mt-4" x-show="kind === 'medication'" x-cloak>
            <p class="form-label">服用する時間帯 <span>必須</span></p>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                @foreach(['morning' => '朝', 'noon' => '昼', 'evening' => '夜', 'bedtime' => '就寝前'] as $value => $label)
                    <label class="medication-time-option">
                        <input type="checkbox" name="medication_timings[]" value="{{ $value }}" @checked(in_array($value, old('medication_timings', []), true))>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            <p class="mt-2 text-xs text-stone-500">朝と夜など、複数選べます。</p>
        </div>

        <div class="mt-4 border-t border-stone-100 pt-4">
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="new-schedule-type" class="form-label">実施する日</label>
                    <select id="new-schedule-type" name="schedule_type" class="form-input" x-model="schedule">
                        <option value="daily">毎日</option>
                        <option value="weekdays">曜日を選ぶ</option>
                        <option value="cycle" x-show="kind === 'medication'">内服・休薬周期</option>
                    </select>
                </div>
                <div>
                    <label for="new-starts-on" class="form-label">開始日</label>
                    <input id="new-starts-on" type="date" name="starts_on" value="{{ old('starts_on') }}" class="form-input">
                </div>
                <div>
                    <label for="new-ends-on" class="form-label">終了日</label>
                    <input id="new-ends-on" type="date" name="ends_on" value="{{ old('ends_on') }}" class="form-input">
                </div>
            </div>
            <div class="mt-3 grid grid-cols-4 gap-2 sm:grid-cols-7" x-show="schedule === 'weekdays'" x-cloak>
                @foreach(['日','月','火','水','木','金','土'] as $weekdayValue => $weekdayLabel)
                    <label class="weekday-option">
                        <input type="checkbox" name="weekdays[]" value="{{ $weekdayValue }}" @checked(in_array($weekdayValue, old('weekdays', []), true))>
                        <span>{{ $weekdayLabel }}</span>
                    </label>
                @endforeach
            </div>
            <div class="cycle-settings mt-3" x-show="kind === 'medication' && schedule === 'cycle'" x-cloak>
                <p class="form-help">開始日を周期の1日目として、内服と休薬を繰り返します。</p>
                <div class="mt-2 grid grid-cols-2 gap-3">
                    <div><label class="form-label">内服する日数 <span>必須</span></label><input type="number" name="cycle_on_days" min="1" max="365" value="{{ old('cycle_on_days') }}" class="form-input" placeholder="例：14"></div>
                    <div><label class="form-label">休薬する日数 <span>必須</span></label><input type="number" name="cycle_rest_days" min="1" max="365" value="{{ old('cycle_rest_days') }}" class="form-input" placeholder="例：7"></div>
                </div>
            </div>
        </div>

        @error('title')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        @error('medication_timings')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        @error('weekdays')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        @error('cycle_on_days')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        @error('cycle_rest_days')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        @error('schedule_type')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        @error('starts_on')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        @error('ends_on')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror

        <div class="mt-4 flex justify-end">
            <button class="primary-action" type="submit" x-text="kind === 'medication' ? '＋ お薬を登録' : '＋ 項目を追加'"></button>
        </div>
    </form>
</section>
@endif
@if($project->uses_todos)
<section class="today-section mt-{{ $project->uses_checkins ? '6' : '8' }}"><div class="flex items-center justify-between"><h2 class="today-section__title">やること</h2><a class="text-sm font-semibold text-indigo-700" href="{{ route('todos.create',['project_id'=>$project->id]) }}">＋ 追加</a></div>@if($project->todos->isEmpty())<p class="today-section__empty">関連するやることはありません。</p>@else<ul class="mt-4 space-y-2">@foreach($project->todos as $todo)<li class="today-item"><span class="{{ $todo->is_completed?'line-through text-stone-400':'text-indigo-950' }}">{{ $todo->title }}</span>@if($todo->due_date)<span class="text-xs text-stone-500">{{ $todo->due_date->format('Y/n/j') }}</span>@endif</li>@endforeach</ul>@endif</section>
@endif
@if($project->uses_activity_logs)
<section class="today-section mt-6"><div class="flex items-center justify-between"><h2 class="today-section__title">活動・体調記録</h2><a class="text-sm font-semibold text-indigo-700" href="{{ route('activity-logs.create',['project_id'=>$project->id]) }}">＋ 記録</a></div>@if($project->activityLogs->isEmpty())<p class="today-section__empty">関連する記録はありません。</p>@else<ul class="mt-4 space-y-2">@foreach($project->activityLogs as $log)<li><a class="today-item" href="{{ route('activity-logs.show',$log) }}"><span>{{ $log->title }}</span><span class="text-xs text-stone-500">{{ $log->performed_on->format('Y/n/j') }}</span></a></li>@endforeach</ul>@endif</section>
@endif
</x-site-app>
