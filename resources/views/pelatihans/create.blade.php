@extends('layouts.app')

@section('title', 'Tambah Pelatihan')

@section('content')
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
@endsection
