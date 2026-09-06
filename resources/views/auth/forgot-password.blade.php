<x-guest-layout>
    <h1 class="mb-4 text-center text-2xl font-bold">パスワードの再設定</h1>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('パスワードをお忘れですか？メールアドレスを入力すると、再設定用のリンクをお送りします。') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- メールアドレス Address -->
        <div>
            <x-input-label for="email" :value="__('メールアドレス')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('再設定用リンクを送信') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>


