<x-guest-layout>
    <div class="mb-8">
        <h2 class="text-3xl font-bold mb-2" style="color: var(--color-text); font-size: 30px;">Lupa Kata Sandi</h2>
        <p class="text-sm" style="color: var(--color-muted);">
            Masukkan email Anda untuk menerima tautan reset kata sandi.
        </p>
        <p class="text-xs mt-4" style="color: var(--color-muted);">
            Portal terpadu pelatihan dan layanan peserta, studi kasus Bapelkes DIY
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-6" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div class="mb-4">
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="block mt-2 w-full" style="height: 50px; border-color: var(--color-line);" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex flex-col gap-4 mt-6">
            <button type="submit" class="inline-flex items-center justify-center px-4 py-3 bg-[var(--color-primary)] border border-transparent rounded-[var(--radius-btn)] font-semibold text-sm text-white hover:opacity-90 focus:outline-none transition ease-in-out duration-150 w-full disabled:opacity-50 disabled:cursor-not-allowed" style="border-radius: 9999px;">
                Kirim Tautan Atur Ulang
            </button>

            <div class="text-center">
                <a class="text-sm" style="color: var(--color-primary);" href="{{ route('login') }}">
                    Kembali ke Masuk
                </a>
            </div>
        </div>
    </form>
</x-guest-layout>
