<?php

namespace App\Policies;

use App\Models\Pendaftaran;
use App\Models\User;

class PendaftaranPolicy
{
    /**
     * Determine whether the user can view the pendaftaran.
     * Peserta hanya bisa lihat miliknya; panitia lihat semua.
     */
    public function view(User $user, Pendaftaran $pendaftaran): bool
    {
        if ($user->isPanitia()) {
            return true;
        }

        return $user->id === $pendaftaran->peserta_id;
    }

    /**
     * Determine whether the user can update the pendaftaran.
     * Peserta hanya bisa ubah miliknya dan hanya saat status pending; panitia tidak bisa ubah data diri (hanya verifikasi).
     */
    public function update(User $user, Pendaftaran $pendaftaran): bool
    {
        if ($user->isPanitia()) {
            // Panitia tidak bisa ubah via updateSelf (hanya bisa verifikasi/tolak)
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
        if ($user->isPanitia()) {
            return false;
        }

        return $user->id === $pendaftaran->peserta_id && $pendaftaran->isPending();
    }

    /**
     * Determine whether the user can verify the pendaftaran (panitia only).
     */
    public function verify(User $user, Pendaftaran $pendaftaran): bool
    {
        return $user->isPanitia();
    }

    /**
     * Determine whether the user can reject the pendaftaran (panitia only).
     */
    public function reject(User $user, Pendaftaran $pendaftaran): bool
    {
        return $user->isPanitia();
    }
}
