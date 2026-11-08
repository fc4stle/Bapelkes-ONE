<?php

namespace App\Notifications;

use App\Models\Pendaftaran;
use Illuminate\Notifications\Notification;

class PelatihanSelesai extends Notification
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
            'judul_pelatihan' => $this->pendaftaran->pelatihan->nama,
            'pesan' => 'Pelatihan '.$this->pendaftaran->pelatihan->nama.' telah selesai. Sertifikat dan evaluasi TIYAS dapat diakses.',
            'tipe' => 'pelatihan_selesai',
            'url' => route('pendaftarans.sertifikat', $this->pendaftaran, false),
        ];
    }
}
