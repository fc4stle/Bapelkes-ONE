<x-guest-layout>
    <h2 class="text-2xl font-bold mb-6" style="color: var(--color-text);">Atur Ulang Kata Sandi</h2>

    <div class="mb-4 text-sm" style="color: var(--color-text);">
        Lupa kata sandi? Tidak masalah. Cukup beri tahu kami email Anda dan kami akan mengirimkan tautan untuk mengatur ulang kata sandi.
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-6 gap-4">
            <a class="text-sm" style="color: var(--color-primary);" href="{{ route('login') }}">
                Kembali ke Masuk
            </a>

            <x-primary-button>
                Kirim Tautan Atur Ulang
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
