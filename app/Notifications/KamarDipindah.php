<?php

namespace App\Notifications;

use App\Models\Pendaftaran;
use Illuminate\Notifications\Notification;

class KamarDipindah extends Notification
{
    public function __construct(
        protected Pendaftaran $pendaftaran,
        protected string $nomorKamarBaru
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
            'pesan' => 'Anda telah dipindahkan ke kamar '.$this->nomorKamarBaru.' untuk pelatihan '.$this->pendaftaran->pelatihan->nama.'.',
            'tipe' => 'pindah_kamar',
        ];
    }
}
