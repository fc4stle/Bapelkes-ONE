<x-app-layout>
    <div style="background-color: var(--color-bg);">
        <div class="py-12">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-3xl font-bold" style="color: var(--color-text);">Notifikasi</h1>
                    @if (auth()->user()->unreadNotifications->count() > 0)
                        <form method="POST" action="{{ route('notifikasi.tandai-semua-dibaca') }}" style="display: inline;">
                            @csrf
                            @method('PATCH')
                            <button 
                                type="submit"
                                class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition-colors"
                                style="background-color: var(--color-primary);"
                                onmouseover="this.style.backgroundColor='var(--color-primary-hover)'"
                                onmouseout="this.style.backgroundColor='var(--color-primary)'"
                            >
                                Tandai Semua Dibaca
                            </button>
                        </form>
                    @endif
                </div>

                @if (session('success'))
                    <div class="mb-4 p-4 rounded-lg" style="background-color: rgba(34, 197, 94, 0.1); border-left: 4px solid var(--color-success);">
                        <p style="color: var(--color-success);">{{ session('success') }}</p>
                    </div>
                @endif

                @if ($notifikasi->count() > 0)
                    <div class="space-y-3">
                        @foreach ($notifikasi as $notif)
                            <a href="{{ route('notifikasi.buka', $notif->id) }}" class="block p-4 rounded-lg" style="background-color: var(--color-surface); border: 1px solid #e5e7eb; text-decoration: none; transition: all 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="font-semibold" style="color: var(--color-text);">
                                        {{ $notif->data['judul_pelatihan'] ?? 'Notifikasi' }}
                                    </h3>
                                    <div class="flex gap-2">
                                        @if (!$notif->read_at)
                                            <span class="text-xs px-3 py-1 rounded-lg" style="background-color: var(--color-danger); color: white;">
                                                Belum Dibaca
                                            </span>
                                        @else
                                            <span class="text-xs px-3 py-1" style="color: #999;">Sudah Dibaca</span>
                                        @endif
                                    </div>
                                </div>

                                <p style="color: var(--color-text); margin-bottom: 0.5rem;">
                                    {{ $notif->data['pesan'] ?? '' }}
                                </p>

                                <p style="color: var(--color-text); opacity: 0.6; font-size: 0.875rem;">
                                    {{ $notif->created_at->diffForHumans() }}
                                </p>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-center rounded-lg" style="background-color: var(--color-surface); border: 1px solid #e5e7eb;">
                        <p style="color: var(--color-text);">Tidak ada notifikasi</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
