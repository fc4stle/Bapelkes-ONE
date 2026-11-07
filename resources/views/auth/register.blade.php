<x-guest-layout>
    <h2 class="text-2xl font-bold mb-6" style="color: var(--color-text);">Daftar Akun Baru</h2>

    <p class="text-sm mb-4 p-3 rounded-[var(--radius-card)]" style="background-color: #f0fdf4; color: #15803d;">
        Akun baru otomatis berperan sebagai peserta.
    </p>

    <form method="POST" action="{{ route('register') }}" @submit="submitting = true" x-data="{ submitting: false }" @submit.prevent="if(!submitting) $el.submit()">
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
            <button type="submit" class="inline-flex items-center justify-center px-4 py-2 bg-[var(--color-primary)] border border-transparent rounded-[var(--radius-btn)] font-semibold text-xs text-white uppercase tracking-widest hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:ring-offset-2 transition ease-in-out duration-150 w-full disabled:opacity-50 disabled:cursor-not-allowed" x-bind:disabled="submitting">
                <span x-show="!submitting">Daftar</span>
                <span x-show="submitting" class="inline-flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Memproses...
                </span>
            </button>

            <div class="text-center text-sm">
                <span style="color: var(--color-text);">Sudah punya akun?</span>
                <a class="font-semibold" style="color: var(--color-primary);" href="{{ route('login') }}">
                    Masuk
                </a>
            </div>
        </div>
    </form>
</x-guest-layout>
