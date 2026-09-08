<x-site-app title="管理者・利用状況 | ANATANOHISHO">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold tracking-wide text-stone-500">管理者専用</p>
            <h1 class="mt-1 text-2xl font-bold text-indigo-950">利用状況一覧</h1>
            <p class="mt-2 text-sm text-stone-600">利用状況の確認に必要な最小限の情報を表示しています。</p>
        </div>
        <div class="flex gap-3"><a href="{{ route('admin.surveys.index') }}" class="primary-action">アンケート管理</a><a href="{{ route('usage.index') }}" class="secondary-action">自分の利用状況</a></div>
    </div>

    <section class="mt-7 grid gap-4 sm:grid-cols-2">
        <article class="rounded-3xl border border-violet-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-stone-500">登録ユーザー</p>
            <p class="mt-2 text-4xl font-bold text-indigo-950">{{ $registeredUsers }}<span class="ml-1 text-base">人</span></p>
        </article>
        <article class="rounded-3xl border border-amber-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-stone-500">最近7日間に利用</p>
            <p class="mt-2 text-4xl font-bold text-stone-800">{{ $activeUsers7Days }}<span class="ml-1 text-base">人</span></p>
        </article>
    </section>

    <section class="mt-5 rounded-3xl border border-violet-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-bold text-stone-800">アンケート回答状況</h2>
        <div class="mt-4 grid grid-cols-3 gap-3 text-center">
            <div class="rounded-2xl bg-stone-50 p-3"><p class="text-2xl font-bold">{{ $surveyStatusCounts['pending'] ?? 0 }}</p><p class="text-xs text-stone-500">回答前</p></div>
            <div class="rounded-2xl bg-amber-50 p-3"><p class="text-2xl font-bold">{{ $surveyStatusCounts['in_progress'] ?? 0 }}</p><p class="text-xs text-stone-500">回答中</p></div>
            <div class="rounded-2xl bg-violet-50 p-3"><p class="text-2xl font-bold">{{ $surveyStatusCounts['completed'] ?? 0 }}</p><p class="text-xs text-stone-500">完了</p></div>
        </div>
    </section>

    <section class="mt-6 overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                <thead class="bg-stone-50 text-xs text-stone-500">
                    <tr><th class="px-5 py-3">ユーザー</th><th class="px-4 py-3">7日</th><th class="px-4 py-3">28日</th><th class="px-4 py-3">8週間</th><th class="px-5 py-3">最近の利用</th></tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse($users as $item)
                        <tr>
                            <td class="px-5 py-4"><p class="font-semibold text-stone-800">{{ $item['user']->name }}</p><p class="mt-1 text-xs text-stone-500">{{ $item['user']->email }}</p></td>
                            <td class="px-4 py-4 font-semibold">{{ $item['summary']['windows']['7_days']['active_days'] }}日</td>
                            <td class="px-4 py-4 font-semibold">{{ $item['summary']['windows']['28_days']['active_days'] }}日</td>
                            <td class="px-4 py-4 font-semibold">{{ $item['summary']['windows']['8_weeks']['active_days'] }}日</td>
                            <td class="px-5 py-4 text-stone-600">{{ $item['summary']['last_used_at']?->timezone(config('app.timezone'))->format('Y年n月j日 H:i') ?? '利用記録なし' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-stone-500">一般ユーザーはまだ登録されていません。</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-site-app>
