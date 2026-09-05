@extends('layouts.app')

@section('title', 'Edit Pelatihan')

@section('content')
<div class="bg-white shadow rounded-lg">
    <div class="px-4 py-5 sm:p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Edit Pelatihan</h1>
            <p class="mt-1 text-sm text-gray-500">Perbarui data pelatihan di bawah.</p>
        </div>

        @include('pelatihans._form', [
            'action' => route('pelatihans.update', $pelatihan),
            'method' => 'PUT',
            'pelatihan' => $pelatihan,
            'submitLabel' => 'Perbarui',
        ])
    </div>
</div>
@endsection
