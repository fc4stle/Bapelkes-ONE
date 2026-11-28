<x-guest-layout>
    <div class="mb-8">
        <h2 class="text-3xl font-bold mb-2" style="color: var(--color-text); font-size: 30px;">Atur Ulang Kata Sandi</h2>
        <p class="text-sm" style="color: var(--color-muted);">
            Buat kata sandi baru untuk akun Anda.
        </p>
        <p class="text-xs mt-4" style="color: var(--color-muted);">
            Portal terpadu pelatihan dan layanan peserta, studi kasus Bapelkes DIY
        </p>
    </div>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div class="mb-4">
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="block mt-2 w-full" style="height: 50px; border-color: var(--color-line);" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mb-4">
            <x-input-label for="password" value="Kata sandi Baru" />
            <x-text-input id="password" class="block mt-2 w-full" style="height: 50px; border-color: var(--color-line);" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mb-4">
            <x-input-label for="password_confirmation" value="Konfirmasi Kata sandi" />

            <x-text-input id="password_confirmation" class="block mt-2 w-full"
                                style="height: 50px; border-color: var(--color-line);"
                                type="password"
                                name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex flex-col gap-4 mt-6">
            <button type="submit" class="inline-flex items-center justify-center px-4 py-3 bg-[var(--color-primary)] border border-transparent rounded-[var(--radius-btn)] font-semibold text-sm text-white hover:opacity-90 focus:outline-none transition ease-in-out duration-150 w-full disabled:opacity-50 disabled:cursor-not-allowed" style="border-radius: 9999px;">
                Atur Ulang Kata Sandi
            </button>
        </div>
    </form>
</x-guest-layout>
