<x-guest-layout>
    <h2 class="text-2xl font-bold mb-6" style="color: var(--color-text);">Masuk ke Akun Anda</h2>

    <!-- Error Alert -->
    @if ($errors->any())
        <div role="alert" class="mb-4 p-4 rounded-[var(--radius-card)] text-white" style="background-color: var(--color-danger);">
            @if ($errors->has('email') && $errors->first('email') === trans('auth.failed'))
                Login gagal. Email atau kata sandi salah. Silakan periksa kembali.
            @else
                {{ $errors->first() }}
            @endif
        </div>
    @endif

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" @submit="$nextTick(() => { if (! $event.defaultPrevented) submitting = true })" x-data="{ submitting: false }">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" value="Kata sandi" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded" style="border-color: #d1d5db;" name="remember">
                <span class="ms-2 text-sm" style="color: var(--color-text);">Ingat saya</span>
            </label>
        </div>

        <div class="flex flex-col gap-4 justify-between mt-6">
            <button type="submit" class="inline-flex items-center justify-center px-4 py-2 bg-[var(--color-primary)] border border-transparent rounded-[var(--radius-btn)] font-semibold text-xs text-white uppercase tracking-widest hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:ring-offset-2 transition ease-in-out duration-150 w-full disabled:opacity-50 disabled:cursor-not-allowed" x-bind:disabled="submitting">
                <span x-show="!submitting">Masuk</span>
                <span x-show="submitting" class="inline-flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Memproses...
                </span>
            </button>

            <div class="text-center text-sm">
                <span style="color: var(--color-text);">Belum punya akun?</span>
                <a class="font-semibold" style="color: var(--color-primary);" href="{{ route('register') }}">Daftar</a>
            </div>

            @if (Route::has('password.request'))
                <div class="text-center">
                    <a class="text-sm" style="color: var(--color-primary);" href="{{ route('password.request') }}">
                        Lupa kata sandi?
                    </a>
                </div>
            @endif
        </div>
    </form>
</x-guest-layout>
