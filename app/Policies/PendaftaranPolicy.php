<?php

namespace App\Policies;

use App\Models\Pendaftaran;
use App\Models\User;

class PendaftaranPolicy
{
    /**
     * Determine whether the user can view the pendaftaran.
     * Peserta hanya bisa lihat miliknya; staf (panitia/admin) lihat semua.
     */
    public function view(User $user, Pendaftaran $pendaftaran): bool
    {
        if ($user->isStaf()) {
            return true;
        }

        return $user->id === $pendaftaran->peserta_id;
    }

    /**
     * Determine whether the user can update the pendaftaran.
     * Peserta hanya bisa ubah miliknya dan hanya saat status pending; staf (panitia/admin) tidak bisa ubah data diri (hanya verifikasi).
     */
    public function update(User $user, Pendaftaran $pendaftaran): bool
    {
        if ($user->isStaf()) {
            // Staf tidak bisa ubah via updateSelf (hanya bisa verifikasi/tolak)
            return false;
        }

        return $user->id === $pendaftaran->peserta_id && $pendaftaran->isPending();
    }

    /**
     * Determine whether the user can delete the pendaftaran.
     * Peserta hanya bisa hapus miliknya dan hanya saat status pending.
     */
    public function delete(User $user, Pendaftaran $pendaftaran): bool
    {
        if ($user->isStaf()) {
            return false;
        }

        return $user->id === $pendaftaran->peserta_id && $pendaftaran->isPending();
    }

    /**
     * Determine whether the user can verify the pendaftaran (staf only).
     */
    public function verify(User $user, Pendaftaran $pendaftaran): bool
    {
        return $user->isStaf();
    }

    /**
     * Determine whether the user can reject the pendaftaran (staf only).
     */
    public function reject(User $user, Pendaftaran $pendaftaran): bool
    {
        return $user->isStaf();
    }

    /**
     * Determine whether the user can fill TIYAS evaluation.
     * Peserta hanya bisa isi miliknya, hanya jika:
     * - peserta pemilik pendaftaran
     * - status verifikasi = diverifikasi
     * - pelatihan status = selesai
     * - belum ada tiyasResponse untuk pendaftaran ini
     */
    public function isiTiyas(User $user, Pendaftaran $pendaftaran): bool
    {
        if ($user->isStaf()) {
            return false; // Staf tidak boleh isi
        }

        if ($user->id !== $pendaftaran->peserta_id) {
            return false; // Bukan miliknya
        }

        if (! $pendaftaran->isDiverifikasi()) {
            return false; // Belum diverifikasi
        }

        if (! $pendaftaran->pelatihan->sudahSelesai()) {
            return false; // Pelatihan harus sudah selesai
        }

        if ($pendaftaran->tiyasResponse()->exists()) {
            return false; // Sudah pernah isi
        }

        return true;
    }
}
