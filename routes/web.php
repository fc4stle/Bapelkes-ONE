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

    Route::middleware('role:peserta')->group(function () {
        Route::get('/pelatihan/{pelatihan}/daftar', [PendaftaranController::class, 'create'])->name('pelatihan.daftar');
        Route::post('/pelatihans/{pelatihan}/daftar', [PendaftaranController::class, 'store'])->name('pelatihans.daftar');
        Route::delete('/pendaftarans/{pendaftaran}', [PendaftaranController::class, 'destroy'])->name('pendaftarans.destroy');
    });

    Route::patch('/pendaftarans/{pendaftaran}', [PendaftaranController::class, 'update'])
        ->middleware('role:panitia')
        ->name('pendaftarans.update');
});

Route::resource('pelatihans', PelatihanController::class)
    ->middleware(['auth', 'role:panitia']);

require __DIR__.'/auth.php';
