<x-guest-layout>
    <h1 class="mb-4 text-center text-2xl font-bold">パスワードの確認</h1>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('安全のため、続ける前にパスワードを入力してください。') }}
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- パスワード -->
        <div>
            <x-input-label for="password" :value="__('パスワード')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-end mt-4">
            <x-primary-button>
                {{ __('確認する') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>


