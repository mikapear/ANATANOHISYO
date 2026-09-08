<x-site-app title="プロフィール | ANATANOHISHO">
    <div class="mb-6 flex flex-wrap justify-end gap-3">
        @if(auth()->user()->is_admin)
            <a href="{{ route('admin.usage.index') }}" class="primary-action">管理者画面を開く</a>
        @endif
        <a href="{{ route('usage.index') }}" class="secondary-action">自分の利用状況を見る</a>
    </div>
    <div class="mx-auto max-w-3xl">
        <h1 class="text-2xl font-bold sm:text-3xl">プロフィール</h1>
        <p class="mt-2 text-sm text-stone-500">登録情報やパスワードを確認・変更できます。</p>

        <div class="mt-7 space-y-6">
            <div class="form-panel mt-0">
                @include('profile.partials.update-profile-information-form')
            </div>
            <div class="form-panel mt-0">
                @include('profile.partials.update-password-form')
            </div>
            <div class="form-panel mt-0 border-red-200">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-site-app>
