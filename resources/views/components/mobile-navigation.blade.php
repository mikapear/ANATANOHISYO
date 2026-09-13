<nav class="mobile-bottom-nav" aria-label="メインナビゲーション">
    @php
        $items = [
            ['route' => 'dashboard', 'label' => '今日', 'active' => request()->routeIs('dashboard') || request()->routeIs('reviews.*'), 'icon' => 'today'],
            ['route' => 'care.index', 'label' => 'おくすり', 'active' => request()->routeIs('care.*') || request()->routeIs('treatments.*'), 'icon' => 'care'],
            ['route' => 'todos.index', 'label' => 'やること', 'active' => request()->routeIs('todos.*') || request()->routeIs('projects.*'), 'icon' => 'todo'],
            ['route' => 'goals.index', 'label' => '目標', 'active' => request()->routeIs('goals.*'), 'icon' => 'goal'],
            ['route' => 'activity-logs.index', 'label' => '記録', 'active' => request()->routeIs('activity-logs.*'), 'icon' => 'record'],
        ];
    @endphp
    @foreach($items as $item)
        <a href="{{ route($item['route']) }}" class="mobile-nav-item {{ $item['active'] ? 'mobile-nav-item--active' : '' }}" @if($item['active']) aria-current="page" @endif>
            <span class="mobile-nav-icon" aria-hidden="true">
                @if($item['icon'] === 'today')
                    <svg viewBox="0 0 24 24"><path d="M12 3v2m0 14v2M3 12h2m14 0h2M5.6 5.6 7 7m10 10 1.4 1.4M18.4 5.6 17 7M7 17l-1.4 1.4"/><circle cx="12" cy="12" r="4"/></svg>
                @elseif($item['icon'] === 'care')
                    <svg viewBox="0 0 24 24"><path d="M8 4h8a4 4 0 0 1 0 8H8a4 4 0 0 1 0-8Zm4 0v8m-6 7h12M9 16h6"/></svg>
                @elseif($item['icon'] === 'todo')
                    <svg viewBox="0 0 24 24"><path d="M9 6h11M9 12h11M9 18h11M4 6l1 1 2-2M4 12l1 1 2-2M4 18l1 1 2-2"/></svg>
                @elseif($item['icon'] === 'goal')
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3"/><path d="m15 9 5-5m-3 0h3v3"/></svg>
                @else
                    <svg viewBox="0 0 24 24"><path d="M5 19h4l10-10-4-4L5 15v4Zm8-12 4 4M4 21h16"/></svg>
                @endif
            </span>
            <span>{{ $item['label'] }}</span>
        </a>
    @endforeach
</nav>
