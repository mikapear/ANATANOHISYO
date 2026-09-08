<x-site-app title="利用状況 | ANATANOHISHO">
    @php
        $windows = [
            '7_days' => ['label' => '最近7日間', 'days' => 7],
            '28_days' => ['label' => '最近28日間', 'days' => 28],
            '8_weeks' => ['label' => '最近8週間', 'days' => 56],
        ];
    @endphp

    @if(auth()->user()->is_admin)
        <div class="mb-6 flex justify-end"><a href="{{ route('admin.usage.index') }}" class="primary-action">管理者画面を開く</a></div>
    @endif

    <div class="text-center">
        <p class="text-xs font-semibold tracking-wide text-stone-500">あなた自身の記録だけを表示しています</p>
        <h1 class="mt-1 text-2xl font-bold text-indigo-950">利用状況</h1>
        <p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-stone-600">どのくらい継続して使えているかを振り返るための画面です。回数の多さを競うものではありません。</p>
    </div>
    <section class="mt-7 grid gap-4 sm:grid-cols-3">
        @foreach($windows as $key => $window)
            <article class="rounded-3xl border border-violet-200 bg-white p-5 text-center shadow-sm">
                <p class="text-sm font-semibold text-stone-500">{{ $window['label'] }}</p>
                <p class="mt-2 text-4xl font-bold text-indigo-950">{{ $summary['windows'][$key]['active_days'] }}<span class="ml-1 text-base">日</span></p>
                <p class="mt-2 text-xs text-stone-500">利用があった日 / {{ $window['days'] }}日</p>
            </article>
        @endforeach
    </section>
    <section class="mt-5 rounded-3xl border border-amber-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h2 class="text-lg font-bold text-stone-800">継続して利用した週</h2><p class="mt-1 text-sm text-stone-500">利用記録がある週が続いた数です。</p></div>
            <p class="text-3xl font-bold text-stone-800">{{ $summary['consecutive_active_weeks'] }}<span class="ml-1 text-base">週</span></p>
        </div>
    </section>
    <section class="mt-5 rounded-3xl border border-stone-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-bold text-stone-800">最近28日間の主な利用</h2>
        @php($counts = $summary['windows']['28_days']['event_counts'])
        @if(empty($counts))
            <p class="mt-4 text-sm text-stone-500">まだ利用記録はありません。使い始めると、ここに少しずつ表示されます。</p>
        @else
            <div class="mt-4 divide-y divide-stone-100">
                @foreach($eventLabels as $eventName => $label)
                    @if(($counts[$eventName] ?? 0) > 0)
                        <div class="flex items-center justify-between gap-4 py-3"><span class="text-sm text-stone-700">{{ $label }}</span><span class="font-bold text-indigo-950">{{ $counts[$eventName] }}回</span></div>
                    @endif
                @endforeach
            </div>
        @endif
    </section>
    <section class="mt-5 rounded-2xl bg-violet-50 px-5 py-4 text-sm text-violet-950">
        @if($summary['first_used_at'])
            <p>最初の利用記録：{{ $summary['first_used_at']->timezone(config('app.timezone'))->format('Y年n月j日') }}</p>
            <p class="mt-1">最近の利用記録：{{ $summary['last_used_at']->timezone(config('app.timezone'))->format('Y年n月j日 H:i') }}</p>
        @else
            <p>利用状況は、これから記録されます。</p>
        @endif
    </section>
</x-site-app>
