<?php

namespace App\Notifications;

use App\Models\Pendaftaran;
use App\Models\Pengumuman;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class PengumumanPelatihan extends Notification
{
    public function __construct(
        protected Pengumuman $pengumuman,
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
            'judul_pelatihan' => $this->pengumuman->pelatihan->nama,
            'pesan' => $this->pengumuman->judul.' — '.Str::limit($this->pengumuman->isi, 150 - strlen($this->pengumuman->judul) - 4),
            'tipe' => 'pengumuman',
            'url' => route('pendaftarans.index', [], false),
        ];
    }
}
