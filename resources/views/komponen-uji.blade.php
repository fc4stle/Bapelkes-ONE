<x-app-layout>
    <div class="max-w-4xl mx-auto py-[var(--sp-8)] px-[var(--sp-4)] space-y-[var(--sp-8)]">

        <h1 class="text-[var(--fs-h1)] font-bold text-[var(--color-text)]">Halaman Uji Komponen</h1>

        {{-- Buttons --}}
        <section class="space-y-[var(--sp-4)]">
            <h2 class="text-[var(--fs-h2)] font-semibold text-[var(--color-text)]">Tombol</h2>
            <div class="flex flex-wrap gap-[var(--sp-3)] items-center">
                <x-primary-button>Simpan</x-primary-button>
                <x-secondary-button>Batal</x-secondary-button>
                <x-danger-button>Hapus</x-danger-button>
                <x-primary-button disabled>Nonaktif</x-primary-button>
            </div>
        </section>

        {{-- Status Badge --}}
        <section class="space-y-[var(--sp-4)]">
            <h2 class="text-[var(--fs-h2)] font-semibold text-[var(--color-text)]">Status Badge</h2>
            <div class="flex flex-wrap gap-[var(--sp-3)]">
                <x-status-badge status="pending" />
                <x-status-badge status="approved" />
                <x-status-badge status="rejected" />
            </div>
        </section>

        {{-- Cards --}}
        <section class="space-y-[var(--sp-4)]">
            <h2 class="text-[var(--fs-h2)] font-semibold text-[var(--color-text)]">Kartu Pelatihan</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-[var(--sp-4)]">
                <x-card variant="normal">
                    <h3 class="font-semibold text-[var(--color-text)]">Pelatihan Dasar K3</h3>
                    <p class="text-[var(--fs-caption)] text-gray-500">Kapasitas 30 peserta</p>
                    <x-status-badge status="pending" class="mt-[var(--sp-2)]" />
                </x-card>
                <x-card variant="selected">
                    <h3 class="font-semibold text-[var(--color-text)]">Pelatihan Manajemen Puskesmas</h3>
                    <p class="text-[var(--fs-caption)] text-gray-500">Kapasitas 50 peserta</p>
                    <x-status-badge status="approved" class="mt-[var(--sp-2)]" />
                </x-card>
                <x-card variant="unavailable">
                    <h3 class="font-semibold text-[var(--color-text)]">Pelatihan Gizi Masyarakat</h3>
                    <p class="text-[var(--fs-caption)] text-gray-500">Kuota penuh</p>
                    <x-status-badge status="rejected" class="mt-[var(--sp-2)]" />
                </x-card>
            </div>
        </section>

        {{-- Form field --}}
        <section class="space-y-[var(--sp-4)]">
            <h2 class="text-[var(--fs-h2)] font-semibold text-[var(--color-text)]">Form Field</h2>
            <div>
                <x-input-label for="nama" value="Nama Kegiatan" />
                <x-text-input id="nama" class="mt-1 block w-full" type="text" placeholder="Contoh: Pelatihan Dasar K3" />
            </div>
            <div>
                <x-input-label for="nama-error" value="Nama Kegiatan (contoh error)" />
                <x-text-input id="nama-error" class="mt-1 block w-full border-[var(--color-danger)]" type="text" />
                <x-input-error :messages="['Nama kegiatan wajib diisi.']" class="mt-1" />
            </div>
        </section>

    </div>
</x-app-layout>