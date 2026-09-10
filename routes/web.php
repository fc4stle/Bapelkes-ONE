<?php

use App\Http\Controllers\PelatihanController;
use App\Http\Controllers\PendaftaranController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/awal', function () {
    return view('awal');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/pendaftarans', [PendaftaranController::class, 'index'])->name('pendaftarans.index');

    Route::get('/pelatihan', [PelatihanController::class, 'katalog'])->name('pelatihan.katalog');

    Route::middleware('role:peserta')->group(function () {
        Route::get('/pelatihan/{pelatihan}/daftar', [PendaftaranController::class, 'create'])->name('pelatihan.daftar');
        Route::post('/pelatihans/{pelatihan}/daftar', [PendaftaranController::class, 'store'])->name('pelatihans.daftar');
        Route::delete('/pendaftarans/{pendaftaran}', [PendaftaranController::class, 'destroy'])->name('pendaftarans.destroy');
        Route::get('/pendaftarans/{pendaftaran}/kartu', [PendaftaranController::class, 'kartu'])->name('pendaftarans.kartu');
        Route::get('/pendaftarans/{pendaftaran}/sertifikat', [PendaftaranController::class, 'sertifikat'])->name('pendaftarans.sertifikat');
    });

    Route::patch('/pendaftarans/{pendaftaran}', [PendaftaranController::class, 'update'])
        ->middleware('role:panitia')
        ->name('pendaftarans.update');
});

Route::middleware(['auth', 'role:panitia'])->group(function () {
    Route::get('/panitia/dashboard', [PelatihanController::class, 'dashboard'])->name('panitia.dashboard');
    Route::get('/pelatihan/{pelatihan}/pendaftar', [PelatihanController::class, 'pendaftar'])->name('pelatihan.pendaftar');
    Route::get('/pelatihan/{pelatihan}/presensi', [PelatihanController::class, 'presensi'])->name('pelatihan.presensi');
    Route::post('/pelatihan/{pelatihan}/presensi', [PelatihanController::class, 'prosesPresensi'])->name('pelatihan.presensi.proses');
    Route::patch('/pendaftarans/{pendaftaran}/verifikasi', [PendaftaranController::class, 'verifikasi'])->name('pendaftarans.verifikasi');
    Route::patch('/pendaftarans/{pendaftaran}/tolak', [PendaftaranController::class, 'tolak'])->name('pendaftarans.tolak');
});

Route::resource('pelatihans', PelatihanController::class)
    ->middleware(['auth', 'role:panitia']);

require __DIR__.'/auth.php';
