#!/usr/bin/env php
<?php

use App\Http\Controllers\PendaftaranController;
use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

// Script helper untuk test concurrent kamar.
// Dipanggil oleh ReservasiAsramaTest::test_concurrent_kamar_terakhir()

require __DIR__.'/../../vendor/autoload.php';

$app = require_once __DIR__.'/../../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$userId = $argv[1] ?? null;
$pelatihanId = $argv[2] ?? null;
$checkIn = $argv[3] ?? null;
$checkOut = $argv[4] ?? null;

if (! $userId || ! $pelatihanId) {
    echo json_encode(['error' => 'Missing arguments']);
    exit(1);
}

$user = User::find($userId);
$pelatihan = Pelatihan::find($pelatihanId);

// Simulasikan request HTTP
$request = Request::create(
    route('pelatihans.daftar', $pelatihan),
    'POST',
    [
        'nama' => 'Peserta Concurrent '.$userId,
        'nik' => str_pad($userId, 16, '0'),
        'kontak' => '08123456789',
        'profesi' => 'Perawat',
        'instansi' => 'RS Test',
        'butuh_asrama' => '1',
        'check_in' => $checkIn,
        'check_out' => $checkOut,
    ]
);
$request->setUserResolver(fn () => $user);

$controller = app(PendaftaranController::class);

try {
    $response = $controller->store($request, $pelatihan);
    $pendaftaran = Pendaftaran::where('peserta_id', $userId)
        ->where('pelatihan_id', $pelatihanId)
        ->first();

    echo json_encode([
        'success' => true,
        'kamar_id' => $pendaftaran?->kamar_id,
        'has_kamar' => $pendaftaran?->kamar_id !== null,
        'redirect' => $response->getTargetUrl(),
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
