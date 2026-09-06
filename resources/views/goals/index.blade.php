<x-site-app title="目標 | ANATANOHISHO">
@php
$categories = [
    'exercise' => [
        'title' => 'からだを動かす',
        'help' => '散歩・ストレッチ・運動など',
        'panel' => 'border-violet-200',
        'icon' => '◇',
    ],
    'nutrition' => [
        'title' => '食事・水分',
        'help' => '食べる・飲むを整える',
        'panel' => 'border-amber-200',
        'icon' => '○',
    ],
    'other' => [
        'title' => 'くらしを整える',
        'help' => '休息・睡眠・楽しみなど',
        'panel' => 'border-pink-200',
        'icon' => '⌂',
    ],
];
@endphp

<div>
    <h1 class="text-2xl font-bold text-indigo-950 sm:text-3xl">目標</h1>
    <p class="mt-2 text-sm text-stone-600">無理なく続けたいことを、自分のペースで決めます。</p>
</div>

<div class="mt-5 flex items-end gap-3">
    <img src="{{ asset('images/brand/anatanohisyo-guide.png') }}" alt="案内役" class="h-20 w-16 rounded-xl object-cover">
    <div class="guide-bubble"><span></span><p>今の暮らしに合うものをひとつ選びましょう。小さな目標からで大丈夫ですよ。</p></div>
</div>

<div class="mt-8 grid gap-5 sm:grid-cols-3">
    @foreach($categories as $value => $details)
        @php($categoryGoals = $goals->get($value, collect()))
        <section class="flex flex-col rounded-3xl border {{ $details['panel'] }} bg-white p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-full bg-stone-100 text-xl text-violet-700" aria-hidden="true">{{ $details['icon'] }}</span>
                <div>
                    <h2 class="font-bold text-stone-800">{{ $details['title'] }}</h2>
                    <p class="mt-1 text-xs text-stone-500">{{ $details['help'] }}</p>
                </div>
            </div>

            @if($categoryGoals->isEmpty())
                <p class="mt-5 flex-1 text-sm text-stone-500">登録中の目標はありません。</p>
            @else
                <ul class="mt-5 flex-1 space-y-2">
                    @foreach($categoryGoals->take(3) as $goal)
                        <li class="rounded-2xl bg-stone-50 px-3 py-2 text-sm">
                            <span class="font-semibold text-stone-700">{{ $goal->title }}</span>
                            @unless($goal->is_active)<span class="ml-1 text-xs text-stone-400">お休み中</span>@endunless
                        </li>
                    @endforeach
                </ul>
                <p class="mt-3 text-xs text-stone-500">{{ $categoryGoals->where('is_active', true)->count() }}件 実施中</p>
            @endif

            <a href="{{ route('goals.manage', $value) }}" class="primary-action mt-5 text-center">登録・変更</a>
        </section>
    @endforeach
</div>
</x-site-app>