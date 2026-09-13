<x-site-app title="おくすり・治療 | ANATANOHISHO">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-xs font-semibold tracking-wide text-stone-500">毎日の服薬と治療予定をひとつに</p>
            <h1 class="mt-1 text-2xl font-bold text-indigo-950">おくすり・治療</h1>
        </div>
        <a href="{{ route('calendar.index') }}" class="secondary-action">カレンダーへ</a>
    </div>

    @if($asNeededMedications->isNotEmpty())
        <section class="mt-6 rounded-3xl border border-violet-200 bg-white p-5 shadow-sm sm:p-6">
            <p class="text-xs font-semibold text-violet-600">必要なときのお薬</p>
            <h2 class="mt-1 text-lg font-bold text-stone-800">頓服を使った記録</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($asNeededMedications as $medication)
                    <div class="rounded-2xl border border-violet-100 bg-violet-50/50 p-4">
                        <p class="font-semibold text-stone-800">{{ $medication->title }}</p>
                        @if($medication->dose_amount)<p class="mt-1 text-xs text-stone-500">1回 {{ rtrim(rtrim($medication->dose_amount, '0'), '.') }}{{ $medication->dose_unit }}</p>@endif
                        <form method="POST" action="{{ route('as-needed-medications.store', $medication) }}" class="mt-3">@csrf<button class="primary-action w-full justify-center" type="submit">いま使った</button></form>
                        <div class="mt-3 space-y-2">
                            @foreach($medication->asNeededUsages as $usage)
                                <div class="flex items-center justify-between gap-2 text-sm"><span>今日 {{ $usage->used_at->format('H:i') }}</span><form method="POST" action="{{ route('as-needed-medications.destroy', $usage) }}">@csrf @method('DELETE')<button class="text-xs text-stone-500">取り消す</button></form></div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <div class="mt-5 flex items-end gap-3">
        <img src="{{ asset('images/brand/anatanohisyo-guide.png') }}" alt="案内役" class="h-20 w-16 rounded-xl object-cover">
        <div class="guide-bubble"><span></span><p>毎日のお薬と通院・治療の予定を、ここで分けて確認できます。</p></div>
    </div>

    <div class="mt-8 grid gap-5 sm:grid-cols-2">
        <section class="rounded-3xl border border-amber-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-full bg-amber-100 text-xl" aria-hidden="true">○</span>
                <div><p class="text-xs font-semibold text-stone-500">毎日の確認</p><h2 class="text-lg font-bold text-stone-800">おくすり</h2></div>
            </div>
            @if($medicationProjects->isEmpty())
                <p class="mt-4 text-sm leading-relaxed text-stone-600">登録されているお薬はまだありません。</p>
            @else
                <div class="mt-4 space-y-2">
                    @foreach($medicationProjects as $project)
                        <a href="{{ route('projects.show', $project) }}" class="block rounded-2xl border border-amber-100 bg-amber-50/60 px-4 py-3">
                            <span class="font-semibold text-stone-800">{{ $project->name }}</span>
                            <span class="mt-1 block text-xs text-stone-500">{{ $project->checkinItems->pluck('title')->join('・') }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
            <div class="mt-5">
                <a href="{{ route('medications.create') }}" class="primary-action">{{ $medicationProjects->isEmpty() ? 'おくすりの登録を始める' : 'おくすりを追加する' }}</a>
                @if($checkinProject)
                    <a href="{{ route('projects.show', $checkinProject) }}" class="ml-2 inline-flex py-2 text-sm font-semibold text-stone-500">登録内容を変更</a>
                @endif
            </div>
        </section>

        <section class="rounded-3xl border border-pink-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-full bg-pink-100 text-xl" aria-hidden="true">＋</span>
                <div><p class="text-xs font-semibold text-stone-500">診察・通院など</p><h2 class="text-lg font-bold text-stone-800">診察・治療</h2></div>
            </div>
            @if($upcomingTreatments->isEmpty())
                <p class="mt-4 text-sm leading-relaxed text-stone-600">これからの治療予定はありません。</p>
            @else
                <div class="mt-4 space-y-2">
                    @foreach($upcomingTreatments as $treatment)
                        <div class="rounded-2xl border border-pink-100 bg-pink-50/60 px-4 py-3">
                            <span class="text-xs font-semibold text-pink-700">{{ $treatment->scheduled_on->format('n月j日') }}</span>
                            <span class="ml-2 font-semibold text-stone-800">{{ $treatment->name }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
            <div class="mt-5"><a href="{{ route('treatments.index') }}" class="primary-action">診察予定を確認・登録</a></div>
        </section>
    </div>

    <div class="mt-8 rounded-2xl border border-stone-200 bg-white px-5 py-4 text-sm text-stone-600">
        日々の用事は、<a href="{{ route('todos.index') }}" class="font-semibold text-violet-700">やること</a>にまとめられます。
    </div>
</x-site-app>
