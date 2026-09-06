@csrf
@if($errors->any())
    <div class="rounded-xl border border-red-200 bg-red-50 p-4"><x-input-error :messages="$errors->all()" /></div>
@endif

@php
    $currentTemplate = old('template', $project?->template ?? request('template', 'planning'));
    $featureDefaults = [
        'uses_todos' => $currentTemplate === 'planning',
        'uses_checkins' => $currentTemplate === 'checkin',
        'uses_activity_logs' => $currentTemplate !== 'planning',
        'uses_calendar' => true,
    ];
    $featureValue = fn (string $name, bool $default) => (bool) old($name, $project?->{$name} ?? $featureDefaults[$name] ?? $default);
@endphp

<div x-data="{
    template: '{{ $currentTemplate }}',
    todos: {{ $featureValue('uses_todos', true) ? 'true' : 'false' }},
    checkins: {{ $featureValue('uses_checkins', false) ? 'true' : 'false' }},
    logs: {{ $featureValue('uses_activity_logs', false) ? 'true' : 'false' }},
    calendar: {{ $featureValue('uses_calendar', true) ? 'true' : 'false' }},
    choose(value) {
        this.template = value;
        if (value === 'planning') { this.todos = true; this.checkins = false; this.logs = false; this.calendar = true; }
        if (value === 'checkin') { this.todos = false; this.checkins = true; this.logs = true; this.calendar = true; }
        if (value === 'journal') { this.todos = false; this.checkins = false; this.logs = true; this.calendar = true; }
    }
}" class="space-y-6">
    <div>
        <label class="form-label">暮らしの予定名 <span>必須</span></label>
        <input class="form-input" name="name" value="{{ old('name',$project?->name) }}" maxlength="255" required autofocus>
    </div>

    <fieldset>
        <legend class="form-label">どのように使いますか？ <span>必須</span></legend>
        <div class="grid gap-3 sm:grid-cols-3">
            @foreach([
                'planning' => ['計画を立てる', 'やることや期限を整理します'],
                'checkin' => ['習慣・服薬をチェック', '決まった日の実施を記録します'],
                'journal' => ['活動・体調を記録', '数値や体調、メモを残します'],
            ] as $value => [$title, $description])
                <label class="project-template-card" :class="template === '{{ $value }}' ? 'project-template-card--selected' : ''">
                    <input type="radio" class="sr-only" name="template" value="{{ $value }}" x-model="template" @change="choose('{{ $value }}')">
                    <span class="font-semibold">{{ $title }}</span>
                    <span class="mt-2 block text-xs leading-relaxed text-stone-500">{{ $description }}</span>
                </label>
            @endforeach
        </div>
        <p class="form-help">選んだ後も、使う機能は自由に変更できます。</p>
    </fieldset>

    <fieldset class="rounded-xl border border-[#e5d9ef] bg-[#fff9f7] p-4">
        <legend class="px-2 text-sm font-semibold">この暮らしの予定で使う機能</legend>
        <div class="grid gap-3 sm:grid-cols-2">
            <label class="project-feature-check"><input type="checkbox" name="uses_todos" value="1" x-model="todos"><span><strong>やること</strong><small>期限やTodoを管理</small></span></label>
            <label class="project-feature-check"><input type="checkbox" name="uses_checkins" value="1" x-model="checkins"><span><strong>習慣チェック</strong><small>花丸で実施を記録（次の段階で追加）</small></span></label>
            <label class="project-feature-check"><input type="checkbox" name="uses_activity_logs" value="1" x-model="logs"><span><strong>活動・体調記録</strong><small>できたことやメモを保存</small></span></label>
            <label class="project-feature-check"><input type="checkbox" name="uses_calendar" value="1" x-model="calendar"><span><strong>カレンダー</strong><small>予定と記録を日付で確認</small></span></label>
        </div>
    </fieldset>

    <div>
        <label class="form-label">説明</label>
        <textarea class="form-input min-h-28" name="description" maxlength="5000">{{ old('description',$project?->description) }}</textarea>
    </div>
    <div class="grid gap-5 sm:grid-cols-2">
        <div><label class="form-label">開始日</label><input class="form-input" type="date" name="start_date" value="{{ old('start_date',$project?->start_date?->format('Y-m-d')) }}"></div>
        <div><label class="form-label">期限</label><input class="form-input" type="date" name="due_date" value="{{ old('due_date',$project?->due_date?->format('Y-m-d')) }}"></div>
    </div>
    <div><label class="form-label">状態</label><select class="form-input" name="status">@foreach(['active'=>'進行中','completed'=>'完了','on_hold'=>'保留'] as $v=>$label)<option value="{{ $v }}" @selected(old('status',$project?->status??'active')===$v)>{{ $label }}</option>@endforeach</select></div>
    <div class="flex gap-3"><button class="primary-action">{{ $submitLabel }}</button><a href="{{ $cancelUrl }}" class="secondary-action">キャンセル</a></div>
</div>
