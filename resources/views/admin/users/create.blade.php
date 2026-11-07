<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl" style="color: var(--color-primary);">
            Tambah Pengguna
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.users.store') }}" @submit="$nextTick(() => { if (! $event.defaultPrevented) submitting = true })" class="rounded-[var(--radius-btn)] p-6" style="background-color: white; border: 1px solid #d4a76a;">
                @csrf

                <!-- Nama -->
                <div class="mb-6">
                    <label for="name" class="block text-sm font-semibold mb-2" style="color: var(--color-marun);">
                        Nama
                    </label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" class="w-full px-4 py-2 rounded-[var(--radius-btn)] border" style="border-color: #d4a76a;" required>
                    @error('name')
                        <p class="text-sm mt-2" style="color: #dc2626;">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div class="mb-6">
                    <label for="email" class="block text-sm font-semibold mb-2" style="color: var(--color-marun);">
                        Email
                    </label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" class="w-full px-4 py-2 rounded-[var(--radius-btn)] border" style="border-color: #d4a76a;" required>
                    @error('email')
                        <p class="text-sm mt-2" style="color: #dc2626;">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div class="mb-6">
                    <label for="password" class="block text-sm font-semibold mb-2" style="color: var(--color-marun);">
                        Password (minimal 8 karakter)
                    </label>
                    <input type="password" id="password" name="password" class="w-full px-4 py-2 rounded-[var(--radius-btn)] border" style="border-color: #d4a76a;" required>
                    @error('password')
                        <p class="text-sm mt-2" style="color: #dc2626;">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Konfirmasi Password -->
                <div class="mb-6">
                    <label for="password_confirmation" class="block text-sm font-semibold mb-2" style="color: var(--color-marun);">
                        Konfirmasi Password
                    </label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="w-full px-4 py-2 rounded-[var(--radius-btn)] border" style="border-color: #d4a76a;" required>
                    @error('password_confirmation')
                        <p class="text-sm mt-2" style="color: #dc2626;">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Role -->
                <div class="mb-8">
                    <label for="role" class="block text-sm font-semibold mb-2" style="color: var(--color-marun);">
                        Role
                    </label>
                    <select id="role" name="role" class="w-full px-4 py-2 rounded-[var(--radius-btn)] border" style="border-color: #d4a76a;" required>
                        <option value="">-- Pilih Role --</option>
                        <option value="panitia" @selected(old('role') === 'panitia')>Panitia</option>
                        <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                    </select>
                    @error('role')
                        <p class="text-sm mt-2" style="color: #dc2626;">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Buttons -->
                <div class="flex gap-3">
                    <button type="submit" class="px-6 py-2 rounded-[var(--radius-btn)] text-white font-semibold text-sm" style="background-color: var(--color-primary);">
                        Buat Akun
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="px-6 py-2 rounded-[var(--radius-btn)] font-semibold text-sm border-2 bg-transparent" style="border-color: var(--color-primary); color: var(--color-primary);">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
