@extends('layouts.app')

@section('title', 'Detail Pelatihan')

@section('content')
<div class="bg-white shadow rounded-lg">
    <div class="px-4 py-5 sm:p-6">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $pelatihan->nama }}</h1>
                <p class="mt-1 text-sm text-gray-500">Detail pelatihan</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('pelatihans.edit', $pelatihan) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Edit
                </a>
                <a href="{{ route('pelatihans.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Kembali
                </a>
            </div>
        </div>

        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-gray-500">Deskripsi</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $pelatihan->deskripsi ?? '-' }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500">Tanggal Mulai</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $pelatihan->tanggal_mulai->format('d F Y') }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500">Tanggal Selesai</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $pelatihan->tanggal_selesai->format('d F Y') }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500">Lokasi</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $pelatihan->lokasi }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500">Kuota</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $pelatihan->kuota }} peserta</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500">Status</dt>
                <dd class="mt-1 text-sm text-gray-900">
                    @php
                        $statusColors = [
                            'draft' => 'bg-gray-100 text-gray-800',
                            'dibuka' => 'bg-green-100 text-green-800',
                            'ditutup' => 'bg-yellow-100 text-yellow-800',
                            'selesai' => 'bg-blue-100 text-blue-800',
                        ];
                    @endphp
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$pelatihan->status] ?? 'bg-gray-100 text-gray-800' }}">
                        {{ ucfirst($pelatihan->status) }}
                    </span>
                </dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500">Dibuat</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $pelatihan->created_at->format('d F Y H:i') }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500">Terakhir Diperbarui</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $pelatihan->updated_at->format('d F Y H:i') }}</dd>
            </div>
        </dl>
    </div>
</div>
@endsection
