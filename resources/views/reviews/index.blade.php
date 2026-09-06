<x-site-app title="振り返り | ANATANOHISHO">
    @php
        $isWeek = $period === 'week';
        $periodLabel = $isWeek
            ? $start->format('n月j日').'〜'.$end->format('n月j日')
            : $start->format('Y年n月');

    @endphp

    <div class="flex items-center justify-between gap-4">
        <a class="calendar-move" href="{{ route('reviews.index', ['period' => $period, 'date' => $previous->toDateString()]) }}" aria-label="前の期間">‹</a>
        <div class="text-center">
            <p class="text-xs font-semibold tracking-wide text-stone-500">あなたの歩み</p>
            <h1 class="mt-1 text-2xl font-bold text-indigo-950">{{ $periodLabel }}</h1>
        </div>
        <a class="calendar-move" href="{{ route('reviews.index', ['period' => $period, 'date' => $next->toDateString()]) }}" aria-label="次の期間">›</a>
    </div>

    <div class="mx-auto mt-5 grid max-w-sm grid-cols-2 rounded-2xl border border-violet-200 bg-white p-1">
        <a href="{{ route('reviews.index', ['period' => 'week', 'date' => $start->toDateString()]) }}" class="rounded-xl px-4 py-2 text-center text-sm font-semibold {{ $isWeek ? 'bg-violet-100 text-violet-900' : 'text-stone-500' }}">1週間</a>
        <a href="{{ route('reviews.index', ['period' => 'month', 'date' => $start->toDateString()]) }}" class="rounded-xl px-4 py-2 text-center text-sm font-semibold {{ ! $isWeek ? 'bg-violet-100 text-violet-900' : 'text-stone-500' }}">1か月</a>
    </div>

    <div class="mt-6 flex items-end justify-center gap-3">
        <img src="{{ asset('images/brand/anatanohisyo-guide.png') }}" alt="案内役" class="h-20 w-16 rounded-xl object-cover">
        <div class="guide-bubble"><span></span><p>{{ $reviewMessage }}</p></div>
    </div>

    <section class="mt-8 grid gap-4 sm:grid-cols-2">
        <article class="rounded-3xl border border-pink-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold text-stone-500">服薬の確認</p>
            @if($medicationRate !== null)
                <p class="mt-2 text-3xl font-bold text-stone-800">{{ $medicationRate }}<span class="ml-1 text-base">%</span></p>
                <p class="mt-1 text-sm text-stone-500">{{ $medicationCompleted }} / {{ $medicationScheduled }}回</p>
            @else
                <p class="mt-3 text-sm text-stone-500">この期間に予定された服薬はありません。</p>
            @endif
        </article>

        <article class="rounded-3xl border border-violet-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold text-stone-500">目標の記録</p>
            @if($goalRecordRate !== null)
                <p class="mt-2 text-3xl font-bold text-stone-800">{{ $goalRecordRate }}<span class="ml-1 text-base">%</span></p>
                <p class="mt-1 text-sm text-stone-500">{{ $goalEntries->count() }} / {{ $goalScheduled }}件</p>
            @else
                <p class="mt-3 text-sm text-stone-500">この期間に予定された目標はありません。</p>
            @endif
        </article>
    </section>

    <section class="mt-5 rounded-3xl border border-amber-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-bold text-stone-800">目標とのつきあい方</h2>
        <div class="mt-4 grid grid-cols-3 gap-3 text-center">
            <div class="rounded-2xl bg-amber-50 p-3"><p class="text-2xl font-bold text-stone-800">{{ $goalStatusCounts['completed'] }}</p><p class="mt-1 text-xs text-stone-500">できた</p></div>
            <div class="rounded-2xl bg-violet-50 p-3"><p class="text-2xl font-bold text-stone-800">{{ $goalStatusCounts['partial'] }}</p><p class="mt-1 text-xs text-stone-500">少しできた</p></div>
            <div class="rounded-2xl bg-stone-100 p-3"><p class="text-2xl font-bold text-stone-800">{{ $goalStatusCounts['rest'] }}</p><p class="mt-1 text-xs text-stone-500">休んだ</p></div>
        </div>
    </section>

    <section class="mt-5 grid gap-4 sm:grid-cols-3">
        <article class="rounded-2xl border border-stone-200 bg-white p-4">
            <p class="text-xs font-semibold text-stone-500">運動記録</p>
            <p class="mt-2 text-2xl font-bold text-stone-800">{{ $exerciseEntries->count() }}<span class="ml-1 text-sm">日</span></p>
            @foreach($exerciseTotals as $unit => $total)
                <p class="mt-1 text-xs text-stone-500">合計 {{ rtrim(rtrim(number_format($total, 1, '.', ''), '0'), '.') }}{{ $unit }}</p>
            @endforeach
        </article>
        <article class="rounded-2xl border border-stone-200 bg-white p-4">
            <p class="text-xs font-semibold text-stone-500">食事・水分記録</p>
            <p class="mt-2 text-2xl font-bold text-stone-800">{{ $nutritionEntries->count() }}<span class="ml-1 text-sm">日</span></p>
        </article>
        <article class="rounded-2xl border border-stone-200 bg-white p-4">
            <p class="text-xs font-semibold text-stone-500">できた予定</p>
            <p class="mt-2 text-2xl font-bold text-stone-800">{{ $completedTodos }}<span class="ml-1 text-sm">件</span></p>
        </article>
    </section>

    <section class="mt-5 rounded-3xl border border-pink-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-bold text-stone-800">体調の記録</h2>
        <div class="mt-4 grid grid-cols-3 gap-3 text-center">
            <div><p class="text-2xl">😊</p><p class="font-bold text-stone-800">{{ $conditionCounts['good'] }}</p><p class="text-xs text-stone-500">よい</p></div>
            <div><p class="text-2xl">🙂</p><p class="font-bold text-stone-800">{{ $conditionCounts['usual'] }}</p><p class="text-xs text-stone-500">ふつう</p></div>
            <div><p class="text-2xl">😣</p><p class="font-bold text-stone-800">{{ $conditionCounts['hard'] }}</p><p class="text-xs text-stone-500">つらい</p></div>
        </div>
        <p class="mt-4 text-center text-xs text-stone-500">記録のない日は、体調が悪かったという意味ではありません。</p>
    </section>

    <div class="mt-8 flex flex-wrap justify-center gap-3">
        <a href="{{ route('calendar.index', ['month' => $start->format('Y-m')]) }}" class="secondary-action">カレンダーに戻る</a>
        <a href="{{ route('goals.index') }}" class="primary-action">次の目標を整える</a>
    </div>
</x-site-app>