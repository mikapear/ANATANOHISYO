@php
$categoryLabels=['exercise'=>['からだを動かす','散歩・ストレッチ・運動など'],'nutrition'=>['食事・水分','食べる・飲むを整える'],'other'=>['くらしを整える','休息・睡眠・楽しみなど']];
$timeLabels=['anytime'=>'いつでも','morning'=>'朝','noon'=>'昼','evening'=>'夜','bedtime'=>'就寝前'];
$sourceLabels=['self'=>'自分で決めた','family'=>'家族と相談した','clinician'=>'医療者と相談した'];
$weekdayLabels=['日','月','火','水','木','金','土'];
@endphp
<x-site-app title="{{ $categoryLabels[$category][0] }} | 目標 | ANATANOHISHO">

<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <a href="{{ route('goals.index') }}" class="text-sm font-semibold text-violet-700">← 目標の種類を選ぶ</a>
        <h1 class="mt-3 text-2xl font-bold text-indigo-950 sm:text-3xl">{{ $categoryLabels[$category][0] }}</h1>
        <p class="mt-2 text-sm text-stone-600">{{ $categoryLabels[$category][1] }}目標を登録・変更します。</p>
    </div>
</div>

<div class="mt-5 flex items-end gap-3">
    <img src="{{ asset('images/brand/anatanohisyo-guide.png') }}" alt="案内役" class="h-20 w-16 rounded-xl object-cover">
    <div class="guide-bubble"><span></span><p>無理のない内容から始めましょう。体調が悪い日は休むことも大切ですよ。</p></div>
</div>

<form method="POST" action="{{ route('goals.store') }}" class="goal-form-panel mt-8" x-data="{ schedule: '{{ old('schedule_type','daily') }}' }">
    @csrf
    <div class="flex items-center justify-between gap-3">
        <div><p class="today-section__eyebrow">新しい目標</p><h2 class="today-section__title">{{ $categoryLabels[$category][0] }}目標を作る</h2></div>
    </div>

    @if($errors->any())<div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4"><x-input-error :messages="$errors->all()"/></div>@endif

    <input type="hidden" name="category" value="{{ $category }}">

    <div class="mt-5">
        <label class="form-label">目標 <span>必須</span></label>
        <input name="title" value="{{ old('title') }}" class="form-input" maxlength="100" placeholder="{{ $category === 'exercise' ? '例：10分歩く、ストレッチをする' : ($category === 'nutrition' ? '例：水分を6杯とる、朝食を食べる' : '例：23時までに休む、趣味の時間を作る') }}" required>
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-3">
        <div><label class="form-label">目標量</label><input type="number" step="0.01" min="0.01" name="target_amount" value="{{ old('target_amount') }}" class="form-input" placeholder="例：10"></div>
        <div><label class="form-label">単位</label><input name="target_unit" value="{{ old('target_unit') }}" maxlength="30" class="form-input" placeholder="分、回、杯など"></div>
        <div><label class="form-label">時間帯</label><select name="time_of_day" class="form-input">@foreach($timeLabels as $value=>$label)<option value="{{ $value }}" @selected(old('time_of_day','anytime')===$value)>{{ $label }}</option>@endforeach</select></div>
    </div>

    <div class="mt-4 border-t border-stone-100 pt-4">
        <div class="grid gap-4 sm:grid-cols-3">
            <div><label class="form-label">取り組む日</label><select name="schedule_type" class="form-input" x-model="schedule"><option value="daily">毎日</option><option value="weekdays">曜日を選ぶ</option></select></div>
            <div><label class="form-label">開始日</label><input type="date" name="starts_on" value="{{ old('starts_on') }}" class="form-input"></div>
            <div><label class="form-label">終了日</label><input type="date" name="ends_on" value="{{ old('ends_on') }}" class="form-input"></div>
        </div>
        <div class="mt-3 grid grid-cols-4 gap-2 sm:grid-cols-7" x-show="schedule==='weekdays'" x-cloak>
            @foreach($weekdayLabels as $value=>$label)<label class="weekday-option"><input type="checkbox" name="weekdays[]" value="{{ $value }}" @checked(in_array($value,old('weekdays',[]),true))><span>{{ $label }}</span></label>@endforeach
        </div>
    </div>

    <div class="mt-4">
        <label class="form-label">この目標を決めた人</label>
        <select name="decided_with" class="form-input">@foreach($sourceLabels as $value=>$label)<option value="{{ $value }}" @selected(old('decided_with','self')===$value)>{{ $label }}</option>@endforeach</select>
    </div>

    <div class="mt-4"><label class="form-label">自分へのメモ</label><textarea name="note" maxlength="1000" class="form-input min-h-24" placeholder="例：つらい日は休む。無理のない範囲で行う。">{{ old('note') }}</textarea></div>
    <div class="mt-5 flex justify-end"><button class="primary-action">＋ 目標を登録</button></div>
</form>

<section class="mt-10">
    <div class="flex items-center justify-between"><h2 class="text-xl font-bold text-indigo-950">登録した目標</h2><span class="text-xs text-stone-500">{{ $goals->where('is_active',true)->count() }}件 実施中</span></div>
    @if($goals->isEmpty())
        <div class="empty-panel">目標はまだありません。</div>
    @else
        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            @foreach($goals as $goal)
                <article class="goal-card {{ !$goal->is_active ? 'goal-card--inactive' : '' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div><span class="goal-category goal-category--{{ $goal->category }}">{{ $categoryLabels[$goal->category][0] }}</span><h3 class="mt-2 font-semibold text-indigo-950">{{ $goal->title }}</h3></div>
                        <span class="schedule-badge">{{ $goal->is_active ? '実施中' : 'お休み中' }}</span>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2 text-xs text-stone-500">
                        @if($goal->target_amount)<span>{{ rtrim(rtrim($goal->target_amount,'0'),'.') }} {{ $goal->target_unit }}</span>@endif
                        <span>{{ $timeLabels[$goal->time_of_day] }}</span>
                        <span>{{ $goal->schedule_type==='daily' ? '毎日' : '曜日指定' }}</span>
                        <span>{{ $sourceLabels[$goal->decided_with] }}</span>
                    </div>
                    @if($goal->note)<p class="mt-3 text-sm leading-relaxed text-stone-600">{{ $goal->note }}</p>@endif

                    <details class="schedule-edit-details mt-4" x-data="{ schedule: '{{ $goal->schedule_type }}' }">
                        <summary>目標を編集</summary>
                        <form method="POST" action="{{ route('goals.update',$goal) }}" class="mt-3 space-y-3">
                            @csrf @method('PATCH')
                            <div class="grid gap-3 sm:grid-cols-2">
                                <input type="hidden" name="category" value="{{ $category }}">
                                <div><label class="form-label">目標</label><input name="title" value="{{ $goal->title }}" class="form-input" maxlength="100" required></div>
                                <div><label class="form-label">目標量</label><input type="number" step="0.01" min="0.01" name="target_amount" value="{{ $goal->target_amount }}" class="form-input"></div>
                                <div><label class="form-label">単位</label><input name="target_unit" value="{{ $goal->target_unit }}" maxlength="30" class="form-input"></div>
                                <div><label class="form-label">時間帯</label><select name="time_of_day" class="form-input">@foreach($timeLabels as $value=>$label)<option value="{{ $value }}" @selected($goal->time_of_day===$value)>{{ $label }}</option>@endforeach</select></div>
                                <div><label class="form-label">取り組む日</label><select name="schedule_type" class="form-input" x-model="schedule"><option value="daily">毎日</option><option value="weekdays">曜日指定</option></select></div>
                                <div><label class="form-label">開始日</label><input type="date" name="starts_on" value="{{ $goal->starts_on?->toDateString() }}" class="form-input"></div>
                                <div><label class="form-label">終了日</label><input type="date" name="ends_on" value="{{ $goal->ends_on?->toDateString() }}" class="form-input"></div>
                                <div><label class="form-label">決めた人</label><select name="decided_with" class="form-input">@foreach($sourceLabels as $value=>$label)<option value="{{ $value }}" @selected($goal->decided_with===$value)>{{ $label }}</option>@endforeach</select></div>
                            </div>
                            <div class="grid grid-cols-4 gap-2 sm:grid-cols-7" x-show="schedule==='weekdays'" x-cloak>@foreach($weekdayLabels as $value=>$label)<label class="weekday-option"><input type="checkbox" name="weekdays[]" value="{{ $value }}" @checked(in_array($value,$goal->weekdays??[],true))><span>{{ $label }}</span></label>@endforeach</div>
                            <div><label class="form-label">メモ</label><textarea name="note" maxlength="1000" class="form-input min-h-20">{{ $goal->note }}</textarea></div>
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <label class="flex items-center gap-2 text-sm text-stone-600"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($goal->is_active)>実施中にする</label>
                                <button class="secondary-action">保存</button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('goals.destroy',$goal) }}" class="mt-3 text-right" onsubmit="return confirm('この目標を削除しますか？')">@csrf @method('DELETE')<button class="text-action text-red-600">削除</button></form>
                    </details>
                </article>
            @endforeach
        </div>
    @endif
</section>
</x-site-app>
