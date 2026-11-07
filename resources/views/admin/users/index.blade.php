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
                <div class="mb-4 p-4 rounded-[var(--radius-btn)] bg-green-50 border border-green-200 text-green-800">
                    {{ $message }}
                </div>
            @endif
            @if ($message = session('error'))
                <div class="mb-4 p-4 rounded-[var(--radius-btn)] bg-red-50 border border-red-200 text-red-800">
                    {{ $message }}
                </div>
            @endif

            <!-- Search & Filter -->
            <div class="mb-6 flex flex-col sm:flex-row gap-3">
                <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col sm:flex-row gap-3 w-full">
                    <div class="flex-1">
                        <input type="text" name="q" placeholder="Cari nama atau email..." value="{{ $q }}" class="w-full px-4 py-2 rounded-[var(--radius-btn)] border" style="border-color: #d4a76a;">
                    </div>
                    <div>
                        <select name="role" class="px-4 py-2 rounded-[var(--radius-btn)] border" style="border-color: #d4a76a;">
                            <option value="">Semua Role</option>
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
                <div class="overflow-x-auto rounded-[var(--radius-btn)] border" style="border-color: #d4a76a;">
                    <table class="w-full">
                        <thead>
                            <tr style="background-color: var(--color-krem);">
                                <th class="px-4 py-3 text-left text-sm font-semibold" style="color: var(--color-marun);">Nama</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold" style="color: var(--color-marun);">Email</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold" style="color: var(--color-marun);">Role</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold" style="color: var(--color-marun);">Tanggal Daftar</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold" style="color: var(--color-marun);">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr class="border-t" style="border-color: #d4a76a;">
                                    <td class="px-4 py-3 text-sm">{{ $user->name }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $user->email }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        @if ($user->isAdmin())
                                            <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold" style="background-color: #fdf2f4; color: #7A1F2B;">Admin</span>
                                        @else
                                            <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold" style="background-color: #fdf2f4; color: #7A1F2B;">Panitia</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm">{{ $user->created_at->format('d M Y') }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        @if ($user->id !== auth()->user()->id)
                                            <form method="POST" action="{{ route('admin.users.updateRole', $user) }}" class="inline-flex items-center gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <select name="role" class="px-2 py-1 text-xs rounded border" style="border-color: #d4a76a;">
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
                <div class="text-center py-12 rounded-[var(--radius-btn)] border-2 border-dashed" style="border-color: #d4a76a; background-color: var(--color-krem);">
                    <p class="text-gray-500">Tidak ada pengguna.</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
