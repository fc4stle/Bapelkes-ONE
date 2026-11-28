<x-guest-layout>
    <div class="mb-8">
        <h2 class="text-3xl font-bold mb-2" style="color: var(--color-text); font-size: 30px;">Konfirmasi Kata Sandi</h2>
        <p class="text-sm" style="color: var(--color-muted);">
            Masukkan kata sandi Anda lagi untuk melanjutkan.
        </p>
        <p class="text-xs mt-4" style="color: var(--color-muted);">
            Portal terpadu pelatihan dan layanan peserta, studi kasus Bapelkes DIY
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <div class="mb-4">
            <x-input-label for="password" value="Kata sandi" />

            <x-text-input id="password" class="block mt-2 w-full"
                            style="height: 50px; border-color: var(--color-line);"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex flex-col gap-4 mt-6">
            <button type="submit" class="inline-flex items-center justify-center px-4 py-3 bg-[var(--color-primary)] border border-transparent rounded-[var(--radius-btn)] font-semibold text-sm text-white uppercase tracking-widest hover:opacity-90 focus:outline-none transition ease-in-out duration-150 w-full disabled:opacity-50 disabled:cursor-not-allowed" style="border-radius: 9999px;">
                Konfirmasi
            </button>
        </div>
    </form>
</x-guest-layout>
