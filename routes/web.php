<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AsramaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KamarController;
use App\Http\Controllers\PelatihanController;
use App\Http\Controllers\PendaftaranController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TiyasController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('beranda');
});

// Publik: Katalog dan Detail Pelatihan (tamu bisa lihat)
Route::get('/pelatihan', [PelatihanController::class, 'katalog'])->name('pelatihan.katalog');
Route::get('/pelatihan/{pelatihan}/detail', [PelatihanController::class, 'detail'])->name('pelatihan.detail');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified', 'check_user_active'])->name('dashboard');

Route::middleware(['auth', 'check_user_active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/pendaftarans', [PendaftaranController::class, 'index'])->name('pendaftarans.index');

    // Notifikasi
    Route::get('/notifikasi', [PendaftaranController::class, 'notifikasiIndex'])->name('notifikasi.index');
    Route::get('/notifikasi/{notification}/buka', [PendaftaranController::class, 'bukaNotiifikasi'])->name('notifikasi.buka');
    Route::patch('/notifikasi/baca-semua', [PendaftaranController::class, 'tandaiSemuaDibaca'])->name('notifikasi.tandai-semua-dibaca');
    Route::patch('/notifikasi/{notification}/baca', [PendaftaranController::class, 'tandaiDibaca'])->name('notifikasi.tandai-dibaca');

    Route::middleware('role:peserta')->group(function () {
        Route::get('/pelatihan/{pelatihan}/daftar', [PendaftaranController::class, 'create'])->name('pelatihan.daftar');
        Route::post('/pelatihans/{pelatihan}/daftar', [PendaftaranController::class, 'store'])->name('pelatihans.daftar');
        Route::get('/pendaftarans/{pendaftaran}/edit', [PendaftaranController::class, 'edit'])->name('pendaftarans.edit');
        Route::put('/pendaftarans/{pendaftaran}', [PendaftaranController::class, 'updateSelf'])->name('pendaftarans.updateSelf');
        Route::delete('/pendaftarans/{pendaftaran}', [PendaftaranController::class, 'destroy'])->name('pendaftarans.destroy');
        Route::get('/pendaftarans/{pendaftaran}/kartu', [PendaftaranController::class, 'kartu'])->name('pendaftarans.kartu');
        Route::get('/pendaftarans/{pendaftaran}/sertifikat', [PendaftaranController::class, 'sertifikat'])->name('pendaftarans.sertifikat');
        Route::get('/pendaftarans/{pendaftaran}/tiyas', [TiyasController::class, 'form'])->name('tiyas.form');
        Route::post('/pendaftarans/{pendaftaran}/tiyas', [TiyasController::class, 'simpan'])->name('tiyas.simpan');
    });

    Route::patch('/pendaftarans/{pendaftaran}', [PendaftaranController::class, 'update'])
        ->middleware('role:panitia,admin')
        ->name('pendaftarans.update');
});

Route::middleware(['auth', 'role:panitia,admin'])->group(function () {
    Route::get('/panitia/dashboard', [PelatihanController::class, 'dashboard'])->name('panitia.dashboard');
    Route::get('/pelatihan/{pelatihan}/pendaftar', [PelatihanController::class, 'pendaftar'])->name('pelatihan.pendaftar');
    Route::get('/pelatihan/{pelatihan}/pendaftar/export', [PelatihanController::class, 'exportPendaftar'])->name('pelatihan.pendaftar.export');
    Route::get('/pelatihan/{pelatihan}/presensi', [PelatihanController::class, 'presensi'])->name('pelatihan.presensi');
    Route::post('/pelatihan/{pelatihan}/presensi', [PelatihanController::class, 'prosesPresensi'])->name('pelatihan.presensi.proses');
    Route::patch('/pendaftarans/{pendaftaran}/verifikasi', [PendaftaranController::class, 'verifikasi'])->name('pendaftarans.verifikasi');
    Route::patch('/pendaftarans/{pendaftaran}/tolak', [PendaftaranController::class, 'tolak'])->name('pendaftarans.tolak');
    Route::get('/pelatihans/{pelatihan}/tiyas', [TiyasController::class, 'hasil'])->name('tiyas.hasil');
    Route::get('/tiyas/{tiyasResponse}', [TiyasController::class, 'detail'])->name('tiyas.detail');
    Route::patch('/tiyas/{tiyasResponse}/catatan', [TiyasController::class, 'catatan'])->name('tiyas.catatan');
    Route::get('/pelatihans/{pelatihan}/tiyas/export', [TiyasController::class, 'export'])->name('tiyas.export');

    // Asrama & Kamar Management
    Route::get('/panitia/asrama', [AsramaController::class, 'index'])->name('panitia.asrama.index');
    Route::get('/panitia/asrama/{asrama}', [AsramaController::class, 'show'])->name('panitia.asrama.show');
    Route::post('/panitia/asrama/{asrama}/kamar', [KamarController::class, 'store'])->name('panitia.kamar.store');
    Route::patch('/panitia/kamar/{kamar}', [KamarController::class, 'update'])->name('panitia.kamar.update');
    Route::patch('/panitia/pendaftaran/{pendaftaran}/kamar', [PendaftaranController::class, 'pindahKamar'])->name('panitia.pendaftaran.pindah-kamar');
});

Route::resource('pelatihans', PelatihanController::class)
    ->middleware(['auth', 'role:panitia,admin']);

// Admin routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', AdminUserController::class)->only(['index', 'create', 'store']);
    Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.updateRole');
    Route::patch('/users/{user}/password', [AdminUserController::class, 'resetPassword'])->name('users.resetPassword');
    Route::patch('/users/{user}/nonaktifkan', [AdminUserController::class, 'nonaktifkan'])->name('users.nonaktifkan');
    Route::patch('/users/{user}/aktifkan', [AdminUserController::class, 'aktifkan'])->name('users.aktifkan');
});

require __DIR__.'/auth.php';
