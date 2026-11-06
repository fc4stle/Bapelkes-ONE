<x-guest-layout>
    <h2 class="text-2xl font-bold mb-6" style="color: var(--color-text);">Verifikasi Email</h2>

    <div class="mb-4 text-sm" style="color: var(--color-text);">
        Terima kasih telah mendaftar! Sebelum memulai, bisakah Anda verifikasi alamat email Anda dengan mengklik tautan yang baru kami kirimkan? Jika Anda tidak menerima email, kami akan senang mengirimkan yang lain.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm p-3 rounded-[var(--radius-card)]" style="background-color: #f0fdf4; color: #15803d;">
            Tautan verifikasi baru telah dikirim ke alamat email yang Anda berikan saat mendaftar.
        </div>
    @endif

    <div class="mt-6 flex flex-col gap-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button class="w-full justify-center">
                    Kirim Ulang Email Verifikasi
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="w-full text-center underline text-sm" style="color: var(--color-primary);">
                Keluar
            </button>
        </form>
    </div>
</x-guest-layout>
