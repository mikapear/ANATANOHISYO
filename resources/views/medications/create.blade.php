<x-site-app title="お薬を登録 | ANATANOHISHO">
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('care.index') }}" class="text-sm font-semibold text-violet-700">← おくすり・治療へ</a>
        <h1 class="mt-3 text-2xl font-bold text-indigo-950">お薬を登録する</h1>
        <p class="mt-2 text-sm text-stone-500">薬の名前と服用する時間帯を入力すると、今日の確認に表示されます。</p>

        <form method="POST" action="{{ route('medications.store') }}" class="form-panel" x-data="{ schedule: '{{ old('schedule_type', 'daily') }}', medicationType: '{{ old('medication_type', 'scheduled') }}' }">
            @csrf
            @if($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 p-4"><x-input-error :messages="$errors->all()"/></div>
            @endif

            <div>
                <label for="medication-title" class="form-label">薬の名前 <span>必須</span></label>
                <input id="medication-title" name="title" value="{{ old('title') }}" maxlength="100" class="form-input" placeholder="例：〇〇錠" required autofocus>
            </div>

            <fieldset>
                <legend class="form-label">お薬の使い方 <span>必須</span></legend>
                <div class="mt-2 grid grid-cols-2 gap-2">
                    <label class="medication-time-option"><input type="radio" name="medication_type" value="scheduled" x-model="medicationType"><span>決まった時間に飲む</span></label>
                    <label class="medication-time-option"><input type="radio" name="medication_type" value="as_needed" x-model="medicationType"><span>必要なときに使う</span></label>
                </div>
            </fieldset>

            <fieldset x-show="medicationType === 'scheduled'" x-cloak>
                <legend class="form-label">服用する時間帯 <span>必須</span></legend>
                <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach(['morning' => '朝', 'noon' => '昼', 'evening' => '夜', 'bedtime' => '就寝前'] as $value => $label)
                        <label class="medication-time-option">
                            <input type="checkbox" name="medication_timings[]" value="{{ $value }}" @checked(in_array($value, old('medication_timings', []), true))>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <p class="form-help">朝と夜など、複数選べます。</p>
            </fieldset>

            <div class="grid gap-5 sm:grid-cols-2">
                <div><label class="form-label">1回の量</label><input type="number" name="dose_amount" min="0.01" max="999999.99" step="0.01" value="{{ old('dose_amount') }}" class="form-input" placeholder="例：1"></div>
                <div>
                    <label for="medication-dose-unit" class="form-label">単位</label>
                    <input id="medication-dose-unit" name="dose_unit" list="medication-unit-options" maxlength="30" value="{{ old('dose_unit') }}" class="form-input" placeholder="候補から選択、または入力">
                    <datalist id="medication-unit-options"><x-medication-unit-options /></datalist>
                    <p class="form-help">錠・包・mLなどから選べます。候補にない単位は直接入力できます。</p>
                </div>
            </div>

            <div x-show="medicationType === 'scheduled'" x-cloak>
                <label for="medication-schedule" class="form-label">服用する日</label>
                <select id="medication-schedule" name="schedule_type" class="form-input" x-model="schedule">
                    <option value="daily">毎日</option>
                    <option value="weekdays">曜日を選ぶ</option>
                    <option value="cycle">内服・休薬周期</option>
                </select>
            </div>

            <div class="grid grid-cols-4 gap-2 sm:grid-cols-7" x-show="medicationType === 'scheduled' && schedule === 'weekdays'" x-cloak>
                @foreach(['日','月','火','水','木','金','土'] as $value => $label)
                    <label class="weekday-option"><input type="checkbox" name="weekdays[]" value="{{ $value }}" @checked(in_array($value, old('weekdays', []), true))><span>{{ $label }}</span></label>
                @endforeach
            </div>

            <div class="cycle-settings" x-show="medicationType === 'scheduled' && schedule === 'cycle'" x-cloak>
                <p class="form-help">開始日を周期の1日目として、内服と休薬を繰り返します。</p>
                <div class="mt-3 grid grid-cols-2 gap-3">
                    <div><label class="form-label">内服する日数 <span>必須</span></label><input type="number" name="cycle_on_days" min="1" max="365" value="{{ old('cycle_on_days') }}" class="form-input" placeholder="例：14"></div>
                    <div><label class="form-label">休薬する日数 <span>必須</span></label><input type="number" name="cycle_rest_days" min="1" max="365" value="{{ old('cycle_rest_days') }}" class="form-input" placeholder="例：7"></div>
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div><label class="form-label">開始日</label><input type="date" name="starts_on" value="{{ old('starts_on') }}" class="form-input"></div>
                <div><label class="form-label">終了日</label><input type="date" name="ends_on" value="{{ old('ends_on') }}" class="form-input"></div>
            </div>

            <details class="activity-advanced">
                <summary><span>服用方法・注意事項を追加</span><small>必要なときだけ</small></summary>
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div><label class="form-label">服用方法</label><textarea name="medication_instructions" maxlength="1000" rows="3" class="form-input" placeholder="例：朝食後に水で服用">{{ old('medication_instructions') }}</textarea></div>
                    <div><label class="form-label">注意事項</label><textarea name="medication_precautions" maxlength="1000" rows="3" class="form-input" placeholder="処方時に伝えられた注意事項">{{ old('medication_precautions') }}</textarea></div>
                </div>
            </details>

            <div class="flex gap-3">
                <button class="primary-action" type="submit">お薬を登録する</button>
                <a href="{{ route('care.index') }}" class="secondary-action">キャンセル</a>
            </div>
        </form>
    </div>
</x-site-app>
