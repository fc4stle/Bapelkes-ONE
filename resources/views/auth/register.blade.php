<x-guest-layout>
    <h2 class="text-2xl font-bold mb-6" style="color: var(--color-text);">Daftar Akun Baru</h2>

    <p class="text-sm mb-4 p-3 rounded-[var(--radius-card)]" style="background-color: #f0fdf4; color: #15803d;">
        Akun baru otomatis berperan sebagai peserta.
    </p>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" value="Nama Lengkap" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" value="Kata sandi" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Konfirmasi Kata sandi" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex flex-col gap-4 justify-between mt-6">
            <x-primary-button class="w-full justify-center">
                Daftar
            </x-primary-button>

            <div class="text-center text-sm">
                <span style="color: var(--color-text);">Sudah punya akun?</span>
                <a class="font-semibold" style="color: var(--color-primary);" href="{{ route('login') }}">
                    Masuk
                </a>
            </div>
        </div>
    </form>
</x-guest-layout>
