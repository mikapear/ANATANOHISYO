<x-site-app title="治療予定 | ANATANOHISHO">
    @php
        $typeLabels = [
            'consultation' => '診察',
            'chemotherapy' => '抗がん剤治療',
            'infusion' => '点滴',
            'injection' => '注射',
            'radiation' => '放射線治療',
            'procedure' => '処置・手術',
            'other' => 'その他',
        ];
        $statusLabels = ['scheduled' => '予定', 'completed' => '実施', 'postponed' => '延期', 'cancelled' => '中止', 'changed' => '内容変更'];
        $statusClasses = ['scheduled' => 'bg-stone-100 text-stone-700', 'completed' => 'bg-emerald-100 text-emerald-800', 'postponed' => 'bg-amber-100 text-amber-800', 'cancelled' => 'bg-stone-200 text-stone-500', 'changed' => 'bg-violet-100 text-violet-800'];
    @endphp

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-xs font-semibold tracking-wide text-stone-500">診察日と相談したいことを分かりやすく</p>
            <h1 class="mt-1 text-2xl font-bold text-indigo-950">診察・治療予定</h1>
        </div>
        <a href="{{ route('calendar.index') }}" class="secondary-action">カレンダーへ戻る</a>
    </div>

    <div class="mt-5 flex items-end gap-3">
        <img src="{{ asset('images/brand/anatanohisyo-guide.png') }}" alt="案内役" class="h-20 w-16 rounded-xl object-cover">
        <div class="guide-bubble"><span></span><p>診察日と、相談したいことや診察後に聞いたことを、分かる範囲で残せます。</p></div>
    </div>

    <section class="mt-8 rounded-3xl border border-pink-200 bg-white p-5 shadow-sm sm:p-6">
        <h2 class="text-lg font-bold text-stone-800">新しい診察・治療予定</h2>
        <form method="POST" action="{{ route('treatments.store') }}" class="mt-5 grid gap-4 sm:grid-cols-2" x-data="{ type: '{{ old('treatment_type', 'consultation') }}' }">
            @csrf
            <label class="sm:col-span-2 text-sm font-semibold text-stone-700">
                予定の種類 <span class="text-pink-600">*</span>
                <select class="form-input mt-1" name="treatment_type" x-model="type" required>
                    @foreach($typeLabels as $value => $label)
                        <option value="{{ $value }}" @selected(old('treatment_type', 'consultation') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="sm:col-span-2 text-sm font-semibold text-stone-700" x-show="type === 'other'" x-cloak>
                予定の名前 <span class="text-pink-600">*</span>
                <input class="form-input mt-1" name="name" value="{{ old('name') }}" maxlength="255" :required="type === 'other'" placeholder="例：検査結果の説明">
            </label>
            <label class="text-sm font-semibold text-stone-700">
                予定日 <span class="text-pink-600">*</span>
                <input class="form-input mt-1" type="date" name="scheduled_on" value="{{ old('scheduled_on', request('scheduled_on', today()->toDateString())) }}" required>
            </label>
            <label class="text-sm font-semibold text-stone-700">
                時刻
                <input class="form-input mt-1" type="time" name="scheduled_at" value="{{ old('scheduled_at') }}">
            </label>
            <label class="text-sm font-semibold text-stone-700">
                病院
                <input class="form-input mt-1" name="hospital" value="{{ old('hospital') }}" placeholder="任意">
            </label>
            <label class="text-sm font-semibold text-stone-700">
                診療科
                <input class="form-input mt-1" name="department" value="{{ old('department') }}" placeholder="例：乳腺外科">
            </label>
            <label class="sm:col-span-2 text-sm font-semibold text-stone-700">
                診察前のメモ・相談したいこと
                <textarea class="form-input mt-1" name="note" rows="3" maxlength="2000" placeholder="症状や、先生に聞きたいことなど">{{ old('note') }}</textarea>
            </label>
            <details class="activity-advanced sm:col-span-2" @if(old('cycle_number') || old('status')) open @endif>
                <summary><span>詳しく設定する</span><small>治療クール・状態</small></summary>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <label class="text-sm font-semibold text-stone-700">
                        何クール目
                        <input class="form-input mt-1" type="number" name="cycle_number" value="{{ old('cycle_number') }}" min="1" max="999" placeholder="例：3">
                    </label>
                    <label class="text-sm font-semibold text-stone-700">
                        状態
                        <select class="form-input mt-1" name="status">
                            @foreach($statusLabels as $value => $label)<option value="{{ $value }}" @selected(old('status', 'scheduled') === $value)>{{ $label }}</option>@endforeach
                        </select>
                    </label>
                </div>
            </details>
            @if($errors->any())
                <div class="sm:col-span-2 rounded-2xl bg-pink-50 px-4 py-3 text-sm text-pink-800">
                    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                </div>
            @endif
            <div class="sm:col-span-2">
                <button class="primary-action" type="submit">診察・治療予定を登録する</button>
            </div>
        </form>
    </section>

    <section class="mt-8">
        <h2 class="text-lg font-bold text-stone-800">登録した予定</h2>
        @if($treatments->isEmpty())
            <div class="mt-4 rounded-3xl border border-stone-200 bg-white p-6 text-center text-sm text-stone-500">治療予定はまだありません。</div>
        @else
            <div class="mt-4 space-y-3">
                @foreach($treatments as $treatment)
                    <article class="rounded-3xl border border-violet-200 bg-white p-5 shadow-sm" x-data="{ editing: false }">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="flex flex-wrap items-center gap-2 text-xs font-semibold text-stone-500">
                                    <span class="rounded-full bg-pink-100 px-3 py-1 text-pink-800">{{ $typeLabels[$treatment->treatment_type] }}</span>
                                    <span class="rounded-full px-3 py-1 {{ $statusClasses[$treatment->status] ?? $statusClasses['scheduled'] }}">{{ $statusLabels[$treatment->status] ?? '予定' }}</span>
                                    <span>{{ $treatment->scheduled_on->format('Y年n月j日') }}</span>
                                    @if($treatment->scheduled_at)<span>{{ substr($treatment->scheduled_at, 0, 5) }}</span>@endif
                                </div>
                                <h3 class="mt-2 text-lg font-bold text-stone-800">{{ $treatment->name }}</h3>
                                <p class="mt-1 text-sm text-stone-500">
                                    @if($treatment->cycle_number)第{{ $treatment->cycle_number }}クール @endif
                                    @if($treatment->hospital)・{{ $treatment->hospital }} @endif
                                    @if($treatment->department)・{{ $treatment->department }} @endif
                                </p>
                                @if($treatment->note)<p class="mt-2 text-sm text-stone-600"><span class="font-semibold">相談メモ：</span>{{ $treatment->note }}</p>@endif
                                @if($treatment->visit_summary)<p class="mt-2 whitespace-pre-line text-sm text-stone-600"><span class="font-semibold">診察後：</span>{{ $treatment->visit_summary }}</p>@endif
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" class="secondary-action" @click="editing = !editing">編集</button>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2 border-t border-stone-100 pt-4" aria-label="治療の状態を変更">
                            @foreach($statusLabels as $value => $label)
                                <form method="POST" action="{{ route('treatments.status', $treatment) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $value }}">
                                    <button type="submit" class="rounded-full border px-3 py-1.5 text-xs font-semibold transition {{ $treatment->status === $value ? ($statusClasses[$value].' border-transparent') : 'border-stone-200 bg-white text-stone-500 hover:border-violet-300' }}">{{ $label }}</button>
                                </form>
                            @endforeach
                        </div>

                        <form x-show="editing" x-cloak method="POST" action="{{ route('treatments.update', $treatment) }}" class="mt-5 grid gap-3 border-t border-stone-100 pt-5 sm:grid-cols-2" x-data="{ type: '{{ $treatment->treatment_type }}' }">
                            @csrf
                            @method('PATCH')
                            <select class="form-input sm:col-span-2" name="treatment_type" x-model="type">@foreach($typeLabels as $value => $label)<option value="{{ $value }}" @selected($treatment->treatment_type === $value)>{{ $label }}</option>@endforeach</select>
                            <input x-show="type === 'other'" x-cloak class="form-input sm:col-span-2" name="name" value="{{ $treatment->treatment_type === 'other' ? $treatment->name : '' }}" :required="type === 'other'" placeholder="予定の名前">
                            <input class="form-input" type="date" name="scheduled_on" value="{{ $treatment->scheduled_on->toDateString() }}" required>
                            <input class="form-input" type="time" name="scheduled_at" value="{{ $treatment->scheduled_at ? substr($treatment->scheduled_at, 0, 5) : '' }}">
                            <input class="form-input" type="number" name="cycle_number" value="{{ $treatment->cycle_number }}" min="1" max="999" placeholder="クール数">
                            <input class="form-input" name="hospital" value="{{ $treatment->hospital }}" placeholder="病院">
                            <input class="form-input" name="department" value="{{ $treatment->department }}" placeholder="診療科">
                            <select class="form-input" name="status">@foreach($statusLabels as $value => $label)<option value="{{ $value }}" @selected($treatment->status === $value)>{{ $label }}</option>@endforeach</select>
                            <textarea class="form-input sm:col-span-2" name="note" rows="2">{{ $treatment->note }}</textarea>
                            <div class="flex flex-wrap gap-2 sm:col-span-2">
                                <button class="primary-action" type="submit">変更を保存</button>
                            </div>
                        </form>
                        <form id="treatment-{{ $treatment->id }}" method="POST" action="{{ route('treatments.summary', $treatment) }}" class="mt-4 border-t border-stone-100 pt-4">
                            @csrf @method('PATCH')
                            <label class="text-sm font-semibold text-stone-700">
                                診察後のメモ
                                <textarea class="form-input mt-1" name="visit_summary" rows="3" maxlength="4000" placeholder="説明されたこと、検査結果、薬の変更など">{{ $treatment->visit_summary }}</textarea>
                            </label>
                            <button class="secondary-action mt-2" type="submit">診察後のメモを保存</button>
                        </form>
                        <form method="POST" action="{{ route('treatments.destroy', $treatment) }}" class="mt-3 text-right" onsubmit="return confirm('この治療予定を削除しますか？')">
                            @csrf
                            @method('DELETE')
                            <button class="text-sm font-semibold text-stone-400 hover:text-red-600" type="submit">削除</button>
                        </form>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</x-site-app>
