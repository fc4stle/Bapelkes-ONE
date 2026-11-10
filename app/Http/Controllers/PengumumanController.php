<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePengumumanRequest;
use App\Models\Pelatihan;
use App\Models\Pengumuman;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PengumumanController extends Controller
{
    /**
     * Display pengumuman form and history.
     */
    public function index(Pelatihan $pelatihan)
    {
        $pengumumans = $pelatihan->pengumumans()
            ->with('pengirim')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('pelatihans.pengumuman.index', compact('pelatihan', 'pengumumans'));
    }

    /**
     * Store a new announcement.
     */
    public function store(StorePengumumanRequest $request, Pelatihan $pelatihan)
    {
        // Ambil pendaftaran terverifikasi
        $pendaftarans = $pelatihan->pendaftarans()
            ->where('status_verifikasi', 'diverifikasi')
            ->with('peserta')
            ->get();

        if ($pendaftarans->isEmpty()) {
            return redirect(route('pelatihan.pengumuman.index', $pelatihan))
                ->withErrors(['pengumuman' => 'Belum ada peserta terverifikasi untuk pelatihan ini.']);
        }

        // Cek pengumuman ganda dalam 2 menit terakhir
        $recentPengumuman = Pengumuman::where('pelatihan_id', $pelatihan->id)
            ->where('judul', $request->judul)
            ->where('isi', $request->isi)
            ->where('created_at', '>=', now()->subMinutes(2))
            ->exists();

        if ($recentPengumuman) {
            return redirect(route('pelatihan.pengumuman.index', $pelatihan))
                ->withErrors(['pengumuman' => 'Pengumuman yang sama baru saja dikirim.']);
        }

        // Simpan pengumuman dan kirim notifikasi dalam transaksi
        $pengumuman = DB::transaction(function () use ($request, $pelatihan, $pendaftarans) {
            // Hitung peserta unik (satu pendaftaran per peserta)
            $pesertaIds = $pendaftarans->pluck('peserta_id')->unique();
            $jumlahPenerima = $pesertaIds->count();

            $pengumuman = Pengumuman::create([
                'pelatihan_id' => $pelatihan->id,
                'user_id' => auth()->id(),
                'judul' => $request->judul,
                'isi' => $request->isi,
                'jumlah_penerima' => $jumlahPenerima,
            ]);

            // Bulk insert notifikasi untuk setiap peserta unik
            $notificationData = [];
            $now = now();

            foreach ($pendaftarans->unique('peserta_id') as $pendaftaran) {
                $notificationData[] = [
                    'id' => (string) Str::uuid(),
                    'notifiable_type' => 'App\\Models\\User',
                    'notifiable_id' => $pendaftaran->peserta_id,
                    'type' => 'pengumuman-pelatihan',
                    'data' => json_encode([
                        'pendaftaran_id' => $pendaftaran->id,
                        'judul_pelatihan' => $pelatihan->nama,
                        'pesan' => Str::limit($request->judul.' '.$request->isi, 150),
                        'tipe' => 'pengumuman',
                        'url' => route('pendaftarans.index', [], false),
                    ]),
                    'read_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (! empty($notificationData)) {
                DB::table('notifications')->insert($notificationData);
            }

            return $pengumuman;
        });

        return redirect(route('pelatihan.pengumuman.index', $pelatihan))->with('success', "Pengumuman terkirim ke {$pengumuman->jumlah_penerima} peserta.");
    }
}
