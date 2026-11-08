<?php

namespace App\Notifications;

use App\Models\Pendaftaran;
use Illuminate\Notifications\Notification;

class PendaftaranDitolak extends Notification
{
    public function __construct(
        protected Pendaftaran $pendaftaran
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'pendaftaran_id' => $this->pendaftaran->id,
            'judul_pelatihan' => $this->pendaftaran->pelatihan->judul,
            'pesan' => 'Pendaftaran Anda untuk pelatihan '.$this->pendaftaran->pelatihan->judul.' telah ditolak.',
            'tipe' => 'ditolak',
        ];
    }
}
