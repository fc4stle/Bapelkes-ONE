<x-guest-layout>
    <h2 class="text-2xl font-bold mb-6" style="color: var(--color-text);">Konfirmasi Kata Sandi</h2>

    <div class="mb-4 text-sm" style="color: var(--color-text);">
        Ini adalah area yang aman di aplikasi. Silakan konfirmasi kata sandi Anda sebelum melanjutkan.
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" value="Kata sandi" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-end mt-6">
            <x-primary-button>
                Konfirmasi
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
