<x-site-app title="おくすり・治療 | ANATANOHISHO">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-xs font-semibold tracking-wide text-stone-500">毎日の服薬と治療予定をひとつに</p>
            <h1 class="mt-1 text-2xl font-bold text-indigo-950">おくすり・治療</h1>
        </div>
        <a href="{{ route('calendar.index') }}" class="secondary-action">カレンダーへ</a>
    </div>

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
                @if($checkinProject)
                    <a href="{{ route('projects.show', $checkinProject) }}" class="primary-action">おくすりを登録・変更</a>
                @else
                    <a href="{{ route('projects.create', ['template' => 'checkin']) }}" class="primary-action">おくすりの登録を始める</a>
                @endif
            </div>
        </section>

        <section class="rounded-3xl border border-pink-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-full bg-pink-100 text-xl" aria-hidden="true">＋</span>
                <div><p class="text-xs font-semibold text-stone-500">通院・抗がん剤など</p><h2 class="text-lg font-bold text-stone-800">治療</h2></div>
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
            <div class="mt-5"><a href="{{ route('treatments.index') }}" class="primary-action">治療予定を確認・登録</a></div>
        </section>
    </div>

    <div class="mt-8 rounded-2xl border border-stone-200 bg-white px-5 py-4 text-sm text-stone-600">
        介護、仕事、家庭、旅行、推し活などは、<a href="{{ route('projects.index') }}" class="font-semibold text-violet-700">暮らしの予定</a>にまとめられます。
    </div>
</x-site-app>