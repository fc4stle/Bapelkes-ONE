<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Tambah Pelatihan
        </h2>
    </x-slot>

    <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white shadow rounded-lg">
    <div class="px-4 py-5 sm:p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Tambah Pelatihan</h1>
            <p class="mt-1 text-sm text-gray-500">Isi form di bawah untuk menambahkan pelatihan baru.</p>
        </div>

        @include('pelatihans._form', [
            'action' => route('pelatihans.store'),
            'method' => 'POST',
            'pelatihan' => null,
            'submitLabel' => 'Simpan',
        ])
    </div>
    </div>
    </div>
    </div>
</x-app-layout>
