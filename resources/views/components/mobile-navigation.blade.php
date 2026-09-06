<nav class="mobile-bottom-nav" aria-label="メインナビゲーション">
    <a href="{{ route('calendar.index') }}" class="mobile-nav-item {{ request()->routeIs('calendar.*') || request()->routeIs('dashboard') || request()->routeIs('todos.*') || request()->routeIs('reviews.*') ? 'mobile-nav-item--active' : '' }}"><span aria-hidden="true">□</span><span>カレンダー</span></a>
    <a href="{{ route('care.index') }}" class="mobile-nav-item {{ request()->routeIs('care.*') || request()->routeIs('treatments.*') ? 'mobile-nav-item--active' : '' }}"><span aria-hidden="true">○</span><span>おくすり・治療</span></a>
    <a href="{{ route('projects.index') }}" class="mobile-nav-item {{ request()->routeIs('projects.*') ? 'mobile-nav-item--active' : '' }}"><span aria-hidden="true">⌂</span><span>暮らし</span></a>
    <a href="{{ route('goals.index') }}" class="mobile-nav-item {{ request()->routeIs('goals.*') ? 'mobile-nav-item--active' : '' }}"><span aria-hidden="true">◇</span><span>目標</span></a>
    <a href="{{ route('activity-logs.index') }}" class="mobile-nav-item {{ request()->routeIs('activity-logs.*') ? 'mobile-nav-item--active' : '' }}"><span aria-hidden="true">✎</span><span>記録</span></a>
</nav>