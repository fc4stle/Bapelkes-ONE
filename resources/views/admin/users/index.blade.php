<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl" style="color: var(--color-primary);">
                Kelola Pengguna
            </h2>
            <a href="{{ route('admin.users.create') }}" class="px-4 py-2 rounded-[var(--radius-btn)] text-white text-sm font-semibold" style="background-color: var(--color-primary);">
                Tambah Pengguna
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Flash Messages -->
            @if ($message = session('success'))
                <div class="mb-4 p-4 rounded-[var(--radius-btn)] border-l-4 text-white" style="background-color: var(--color-bg); border-color: var(--color-primary); color: var(--color-primary);">
                    {{ $message }}
                </div>
            @endif
            @if ($message = session('error'))
                <div class="mb-4 p-4 rounded-[var(--radius-btn)] border-l-4" style="background-color: var(--color-bg); border-color: var(--color-primary); color: var(--color-primary);">
                    {{ $message }}
                </div>
            @endif

            <!-- Validation Errors (outside modal) -->
            @if ($errors->any())
                <div class="mb-4 p-4 rounded-[var(--radius-btn)] border-l-4" style="background-color: var(--color-bg); border-color: var(--color-danger); color: var(--color-danger);">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Search & Filter -->
            <div class="mb-6 flex flex-col sm:flex-row gap-3">
                <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col sm:flex-row gap-3 w-full">
                    <div class="flex-1">
                        <input type="text" name="q" placeholder="Cari nama atau email..." value="{{ $q }}" class="w-full px-4 py-2 rounded-[var(--radius-btn)] border" style="border-color: var(--color-accent);">
                    </div>
                    <div>
                        <select name="role" class="px-4 py-2 rounded-[var(--radius-btn)] border" style="border-color: var(--color-accent);">
                            <option value="">Semua Role</option>
                            <option value="peserta" @selected($selectedRole === 'peserta')>Peserta</option>
                            <option value="panitia" @selected($selectedRole === 'panitia')>Panitia</option>
                            <option value="admin" @selected($selectedRole === 'admin')>Admin</option>
                        </select>
                    </div>
                    <button type="submit" class="px-6 py-2 rounded-[var(--radius-btn)] text-white text-sm font-semibold" style="background-color: var(--color-primary);">
                        Cari
                    </button>
                </form>
            </div>

            <!-- Users Table -->
            @if ($users->count() > 0)
                <div class="overflow-x-auto rounded-[var(--radius-btn)] border" style="border-color: var(--color-accent);">
                    <table class="w-full">
                        <thead>
                            <tr style="background-color: var(--color-bg);">
                                <th class="px-4 py-3 text-left text-sm font-semibold" style="color: var(--color-primary);">Nama</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold" style="color: var(--color-primary);">Email</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold" style="color: var(--color-primary);">Role</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold" style="color: var(--color-primary);">Tanggal Daftar</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold" style="color: var(--color-primary);">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr class="border-t" style="border-color: var(--color-accent);" x-data="{ showResetPasswordModal: {{ (string) old('reset_user_id') === (string) $user->id && $errors->has('password') ? 'true' : 'false' }} }">
                                    <td class="px-4 py-3 text-sm">{{ $user->name }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $user->email }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        @if ($user->isAdmin())
                                            <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--color-bg); color: var(--color-primary);">Admin</span>
                                        @else
                                            <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--color-bg); color: var(--color-primary);">Panitia</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm">{{ $user->created_at->format('d M Y') }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex items-center gap-2">
                                            @if ($user->id !== auth()->user()->id)
                                                <!-- Reset Password Button -->
                                                <button @click="showResetPasswordModal = true" type="button" class="text-xs font-semibold px-3 py-1 rounded-[var(--radius-btn)] text-white" style="background-color: var(--color-warning);">
                                                    Atur Ulang Sandi
                                                </button>

                                                <!-- Change Role Form -->
                                                <form method="POST" action="{{ route('admin.users.updateRole', $user) }}" class="inline-flex items-center gap-2">
                                                    @csrf
                                                    @method('PATCH')
                                                    <select name="role" class="px-2 py-1 text-xs rounded border" style="border-color: var(--color-accent);">
                                                        <option value="peserta" @selected($user->role === 'peserta')>Peserta</option>
                                                        <option value="panitia" @selected($user->role === 'panitia')>Panitia</option>
                                                        <option value="admin" @selected($user->role === 'admin')>Admin</option>
                                                    </select>
                                                    <button type="submit" class="text-xs font-semibold px-3 py-1 rounded-[var(--radius-btn)] text-white" style="background-color: var(--color-primary);">
                                                        Ubah
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-xs text-gray-500">—</span>
                                            @endif
                                        </div>

                                        <!-- Reset Password Modal -->
                                        @if ($user->id !== auth()->user()->id)
                                            <div x-show="showResetPasswordModal" style="display: none;" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                                                <div class="bg-white rounded-[var(--radius-btn)] p-6 w-full max-w-sm" style="background-color: var(--color-surface); color: var(--color-text);">
                                                    <h3 class="text-lg font-semibold mb-4" style="color: var(--color-primary);">
                                                        Atur Ulang Sandi
                                                    </h3>
                                                    <p class="text-sm mb-4" style="color: var(--color-text);">
                                                        Atur ulang sandi untuk: <strong>{{ $user->name }}</strong>
                                                    </p>

                                                    <form method="POST" action="{{ route('admin.users.resetPassword', $user) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="reset_user_id" value="{{ $user->id }}">

                                                        <div class="mb-4">
                                                            <label for="password{{ $user->id }}" class="block text-sm font-semibold mb-2" style="color: var(--color-primary);">
                                                                Kata Sandi Baru
                                                            </label>
                                                            <input type="password" id="password{{ $user->id }}" name="password" autocomplete="new-password" class="w-full px-4 py-2 rounded-[var(--radius-btn)] border" style="border-color: var(--color-accent);" required>
                                                        </div>

                                                        <div class="mb-6">
                                                            <label for="password_confirmation{{ $user->id }}" class="block text-sm font-semibold mb-2" style="color: var(--color-primary);">
                                                                Konfirmasi Kata Sandi
                                                            </label>
                                                            <input type="password" id="password_confirmation{{ $user->id }}" name="password_confirmation" autocomplete="new-password" class="w-full px-4 py-2 rounded-[var(--radius-btn)] border" style="border-color: var(--color-accent);" required>
                                                        </div>

                                                        <div class="flex gap-3">
                                                            <button type="submit" class="flex-1 px-4 py-2 rounded-[var(--radius-btn)] text-white font-semibold text-sm" style="background-color: var(--color-primary);">
                                                                Simpan
                                                            </button>
                                                            <button type="button" @click="showResetPasswordModal = false" class="flex-1 px-4 py-2 rounded-[var(--radius-btn)] border font-semibold text-sm" style="border-color: var(--color-accent); color: var(--color-text);">
                                                                Batal
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $users->links() }}
                </div>
            @else
                <div class="text-center py-12 rounded-[var(--radius-btn)] border-2 border-dashed" style="border-color: var(--color-accent); background-color: var(--color-bg);">
                    <p class="text-gray-500">Tidak ada pengguna.</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
