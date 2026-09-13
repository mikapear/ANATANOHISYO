<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'ANATANOHISHO' }}</title>@vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-screen app-shell pb-24 text-gray-900 antialiased sm:pb-0">
    <header class="app-header sticky top-0 z-40 border-b">
        <div class="mx-auto flex min-h-16 max-w-5xl items-center justify-between px-4 py-2 sm:px-6">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 font-semibold text-indigo-900"><img src="{{ asset('images/brand/anatanohisyo-guide.png') }}" alt="" class="h-10 w-10 rounded-full object-cover"><span class="brand-name hidden sm:inline">ANATANOHISHO</span></a>
            <a href="{{ route('profile.edit') }}" class="profile-shortcut sm:hidden" aria-label="プロフィールを開く"><span>{{ mb_substr(Auth::user()->name, 0, 1) }}</span></a>
            <nav class="hidden items-center gap-1 text-sm font-medium sm:flex" aria-label="メインナビゲーション">
                <a class="site-nav-link {{ request()->routeIs('dashboard') ? 'site-nav-link--active' : '' }}" href="{{ route('dashboard') }}">今日</a>
                <a class="site-nav-link {{ request()->routeIs('calendar.*') ? 'site-nav-link--active' : '' }}" href="{{ route('calendar.index') }}">カレンダー</a>
                <a class="site-nav-link {{ request()->routeIs('care.*') || request()->routeIs('treatments.*') ? 'site-nav-link--active' : '' }}" href="{{ route('care.index') }}">おくすり・治療</a>
                <a class="site-nav-link {{ request()->routeIs('todos.*') || request()->routeIs('projects.*') ? 'site-nav-link--active' : '' }}" href="{{ route('todos.index') }}">やること</a>
                <a class="site-nav-link {{ request()->routeIs('goals.*') ? 'site-nav-link--active' : '' }}" href="{{ route('goals.index') }}">目標</a>
                <a class="site-nav-link {{ request()->routeIs('activity-logs.*') ? 'site-nav-link--active' : '' }}" href="{{ route('activity-logs.index') }}">記録</a>
                <a class="site-nav-link {{ request()->routeIs('profile.*') ? 'site-nav-link--active' : '' }}" href="{{ route('profile.edit') }}">プロフィール</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="site-nav-link">ログアウト</button></form>
            </nav>
        </div>
    </header>
    <main class="mx-auto max-w-5xl px-4 py-7 sm:px-6 sm:py-10">@if(session('status'))<div class="mb-6 rounded-2xl border border-violet-200 bg-violet-50 px-4 py-3 text-sm font-medium text-violet-950">{{ session('status') }}</div>@endif{{ $slot }}</main>
    <x-mobile-navigation />
    <footer class="mt-8 app-footer border-t py-6 text-center text-xs text-stone-500">© {{ date('Y') }} ANATANOHISHO</footer>
</body>
</html>
