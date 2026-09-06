<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ANATANOHISHO | {{ config('app.name') }}</title>
    <meta name="description" content="やることと、できたこと。あなたの毎日をそっと支える場所。">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen top-shell text-gray-900 antialiased">
    <header class="app-header border-b">
        <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-4 sm:px-6">
            <a href="{{ url('/') }}" class="flex items-center gap-2 font-semibold text-indigo-900">
                <img src="{{ asset('images/brand/anatanohisyo-guide.png') }}" alt="" class="h-10 w-10 rounded-full object-cover">
                <span class="brand-name">ANATANOHISHO</span>
            </a>
            <nav class="flex items-center gap-2 text-sm font-medium">
                @auth
                    <span class="hidden text-stone-600 sm:inline">{{ Auth::user()->name }}さん</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-indigo-700 px-4 py-2 text-white transition hover:bg-indigo-800">ログアウト</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 text-indigo-800 transition hover:bg-indigo-50">ログイン</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-indigo-700 px-4 py-2 text-white transition hover:bg-indigo-800">新規登録</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-10 sm:px-6 sm:py-14">
        <section class="text-center">
            <div class="flex justify-center">
                <img src="{{ asset('images/brand/anatanohisyo-guide.png') }}" alt="ANATANOHISHOの案内役" class="h-56 w-auto object-contain sm:h-80">
            </div>
            <h1 class="mt-5 text-3xl font-bold tracking-wide text-indigo-950 sm:text-5xl">ANATANOHISHO</h1>
            <p class="mt-4 text-base font-medium text-indigo-900 sm:text-2xl">あなたの毎日、あなたの歩み</p>
            <div class="mx-auto mt-5 max-w-2xl space-y-3 text-center">
                <p class="hidden text-lg font-medium leading-relaxed text-indigo-900 sm:block">やること、できたこと、残しておきたい記録。</p>
                <p class="text-sm font-medium leading-relaxed text-indigo-900 sm:text-lg">「ANATANOHISHO」は、あなたの日々を整理し、<br class="hidden sm:block">小さな一歩をそっと支えるための場所です。</p>
            </div>
        </section>

        @php
            $todayDestination = auth()->check() ? route('calendar.index') : route('login');
            $organizeDestination = auth()->check() ? route('todos.index') : route('login');
            $recordDestination = auth()->check() ? route('activity-logs.create') : route('login');
        @endphp
        <section class="mt-12 grid gap-4 sm:grid-cols-3" aria-label="主な機能">
            <a href="{{ $todayDestination }}" class="feature-tab group">
                <div class="feature-tab__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M7 3v3m10-3v3M4.5 9h15M6 5h12a2 2 0 0 1 2 2v12H4V7a2 2 0 0 1 2-2Z"/><path d="m9 14 2 2 4-4"/></svg>
                </div>
                <h2>カレンダーを見る</h2>
                <p>服薬、目標、予定、体調を、ひと目で確認できます</p>
            </a>
            <a href="{{ $organizeDestination }}" class="feature-tab group">
                <div class="feature-tab__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M9 6h11M9 12h11M9 18h11"/><path d="m3.5 6 1 1 2-2m-3 7 1 1 2-2m-3 7 1 1 2-2"/></svg>
                </div>
                <h2>やることを整理する</h2>
                <p>忘れたくない予定や用事を、分かりやすく整えます</p>
            </a>
            <a href="{{ $recordDestination }}" class="feature-tab group">
                <div class="feature-tab__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M5 4h14v16H5z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
                </div>
                <h2>できたことを残す</h2>
                <p>頑張ったことや日々の記録を、大切に残せます</p>
            </a>
        </section>

        <div class="mt-10 flex items-end justify-center gap-3">
            <img src="{{ asset('images/brand/anatanohisyo-guide.png') }}" alt="ANATANOHISHOの案内役" class="h-20 w-16 shrink-0 rounded-xl object-cover sm:h-24 sm:w-20">
            <div class="guide-bubble"><span aria-hidden="true"></span><p>まずは今日のことから、ひとつずつ整えてみましょう。</p></div>
        </div>
    </main>

    <footer class="app-footer border-t py-6 text-center text-xs text-stone-500">© {{ date('Y') }} ANATANOHISHO</footer>
</body>
</html>






