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

    <form method="POST" action="{{ route('login') }}">
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
            <x-primary-button class="w-full justify-center">
                Masuk
            </x-primary-button>

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
