<?php

namespace App\Http\Controllers;

use App\Enums\MetodePelatihan;
use App\Enums\StatusPelatihan;
use App\Http\Requests\StorePelatihanRequest;
use App\Http\Requests\UpdatePelatihanRequest;
use App\Models\AktivitasLog;
use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Notifications\PelatihanSelesai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PelatihanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pelatihans = Pelatihan::latest()->paginate(10);

        return view('pelatihans.index', compact('pelatihans'));
    }

    /**
     * Display the catalog of currently open pelatihan for peserta.
     */
    public function katalog(Request $request)
    {
        // Validasi dan set default jika invalid (jangan error)
        $search = null;
        $waktu = 'semua';
        $urut = 'terdekat';

        // Cek dan validasi q
        if ($request->filled('q')) {
            $q = $request->query('q');
            if (is_string($q) && strlen($q) <= 100) {
                $search = $q;
            }
        }

        // Cek dan validasi waktu
        if ($request->filled('waktu')) {
            $w = $request->query('waktu');
            if (in_array($w, ['semua', 'akan_datang', 'berlangsung', 'selesai'])) {
                $waktu = $w;
            }
        }

        // Cek dan validasi urut
        if ($request->filled('urut')) {
            $u = $request->query('urut');
            if (in_array($u, ['terdekat', 'terbaru'])) {
                $urut = $u;
            }
        }

        $metode = MetodePelatihan::tryFrom((string) $request->query('metode'));
        $today = today()->toDateString();

        $pelatihans = Pelatihan::query()
            ->where('status', StatusPelatihan::Dibuka)
            // Eager load jumlah pendaftar (terverifikasi) untuk menghindari N+1
            ->withCount(['pendaftarans' => fn ($q) => $q->where('status_verifikasi', 'diverifikasi')])
            // Cari: nama dan lokasi, case-insensitive, escape % dan _
            ->when($search, function ($query) use ($search) {
                $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $search);

                return $query->where(function ($q) use ($escaped) {
                    $q->whereRaw("LOWER(nama) LIKE LOWER(?) ESCAPE '\\'", ['%'.$escaped.'%'])
                        ->orWhereRaw("LOWER(lokasi) LIKE LOWER(?) ESCAPE '\\'", ['%'.$escaped.'%']);
                });
            })
            // Filter waktu
            ->when($waktu !== 'semua', function ($query) use ($waktu, $today) {
                return match ($waktu) {
                    'akan_datang' => $query->whereDate('tanggal_mulai', '>', $today),
                    'berlangsung' => $query->whereDate('tanggal_mulai', '<=', $today)
                        ->whereDate('tanggal_selesai', '>=', $today),
                    'selesai' => $query->whereDate('tanggal_selesai', '<', $today),
                    default => $query,
                };
            })
            // Filter metode
            ->when($metode, fn ($query) => $query->where('metode', $metode))
            // Urut
            ->when($urut === 'terdekat', fn ($query) => $query->orderBy('tanggal_mulai'))
            ->when($urut === 'terbaru', fn ($query) => $query->orderByDesc('tanggal_mulai'))
            // Default jika tidak ada kondisi urut
            ->when($urut !== 'terdekat' && $urut !== 'terbaru', fn ($query) => $query->orderBy('tanggal_mulai'))
            ->paginate(10)
            ->withQueryString();

        return view('pelatihans.katalog', [
            'pelatihans' => $pelatihans,
            'search' => $search,
            'metode' => $metode?->value,
            'waktu' => $waktu,
            'urut' => $urut,
            'hasCriteria' => (bool) ($search || $metode || $waktu !== 'semua'),
        ]);
    }

    /**
     * Show the detail of a pelatihan for peserta.
     */
    public function detail(Pelatihan $pelatihan)
    {
        $sudahDaftar = Auth::user()?->pendaftaranPelatihan($pelatihan);

        return view('pelatihans.detail', compact('pelatihan', 'sudahDaftar'));
    }

    /**
     * Show the list of pendaftar for a pelatihan (panitia only).
     */
    public function pendaftar(Pelatihan $pelatihan)
    {
        $pendaftarans = $pelatihan->pendaftarans()
            ->with('peserta')
            ->latest()
            ->paginate(15);

        return view('pelatihans.pendaftar', compact('pelatihan', 'pendaftarans'));
    }

    /**
     * Export pendaftar to CSV (panitia only).
     */
    public function exportPendaftar(Pelatihan $pelatihan)
    {
        $fileName = 'pendaftar-'.str($pelatihan->nama)->slug('-').'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($pelatihan) {
            $output = fopen('php://output', 'w');

            // BOM UTF-8 agar terbuka benar di Excel
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header CSV
            fputcsv($output, [
                'No',
                'Nama',
                'Email',
                'Instansi/Asal',
                'No HP',
                'Status Verifikasi',
                'Alasan Penolakan',
                'Kehadiran',
                'Butuh Asrama',
                'Sertifikat Diunduh',
                'Tanggal Daftar',
            ]);

            // Data pendaftar dengan eager loading
            $pendaftarans = $pelatihan->pendaftarans()
                ->with('peserta')
                ->orderByDesc('created_at')
                ->cursor();

            $no = 1;
            foreach ($pendaftarans as $pendaftaran) {
                $dataDiri = $pendaftaran->data_diri ?? [];
                $nama = $this->escapeForCSV($dataDiri['nama'] ?? '-');
                $email = $pendaftaran->peserta?->email ?? '-';
                $instansi = $this->escapeForCSV($dataDiri['instansi'] ?? '-');
                $kontak = $this->escapeForCSV($dataDiri['kontak'] ?? '-');
                $statusVerifikasi = $this->escapeForCSV($pendaftaran->status_verifikasi ?? '-');
                $alasanPenolakan = $this->escapeForCSV($pendaftaran->alasan_penolakan ?? '-');
                $kehadiran = $pendaftaran->hadir_at ? $pendaftaran->hadir_at->format('d-m-Y H:i') : 'Belum Hadir';
                $butuhAsrama = $pendaftaran->butuh_asrama ? 'Ya' : 'Tidak';
                $sertifikatDiunduh = $pendaftaran->kode_sertifikat ? 'Ya' : 'Tidak';
                $tanggalDaftar = $pendaftaran->created_at->format('d-m-Y H:i');

                fputcsv($output, [
                    $no,
                    $nama,
                    $email,
                    $instansi,
                    $kontak,
                    $statusVerifikasi,
                    $alasanPenolakan,
                    $kehadiran,
                    $butuhAsrama,
                    $sertifikatDiunduh,
                    $tanggalDaftar,
                ]);

                $no++;
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }

    /**
     * Export rekap kehadiran to CSV (panitia only).
     */
    public function kehadiranExport(Pelatihan $pelatihan)
    {
        $fileName = 'rekap-kehadiran-'.str($pelatihan->nama)->slug('-').'-'.now()->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($pelatihan) {
            $output = fopen('php://output', 'w');

            // BOM UTF-8 agar terbuka benar di Excel
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header CSV
            fputcsv($output, [
                'No',
                'Nama',
                'Email',
                'Profesi',
                'Instansi',
                'Status Verifikasi',
                'Kehadiran',
                'Waktu Hadir',
                'Kamar',
            ]);

            // Data pendaftaran dengan eager loading - hanya diverifikasi, urut nama
            $pendaftarans = $pelatihan->pendaftarans()
                ->where('status_verifikasi', 'diverifikasi')
                ->with(['peserta', 'kamar.asrama'])
                ->orderBy('data_diri->nama')
                ->get();

            $no = 1;
            foreach ($pendaftarans as $pendaftaran) {
                $dataDiri = $pendaftaran->data_diri ?? [];
                $nama = $this->escapeForCSV($dataDiri['nama'] ?? '');
                $email = $this->escapeForCSV($pendaftaran->peserta?->email ?? '');
                $profesi = $this->escapeForCSV($dataDiri['profesi'] ?? '');
                $instansi = $this->escapeForCSV($dataDiri['instansi'] ?? '');
                $statusVerifikasi = $this->escapeForCSV($pendaftaran->status_verifikasi ?? '');

                // Status kehadiran
                $kehadiran = $this->escapeForCSV($pendaftaran->hadir_at ? 'Hadir' : 'Belum');

                // Waktu hadir
                $waktuHadir = $this->escapeForCSV($pendaftaran->hadir_at ? $pendaftaran->hadir_at->format('d/m/Y H:i') : '');

                // Kamar (nomor_kamar + nama asrama, atau kosong)
                $kamar = '';
                if ($pendaftaran->kamar) {
                    $nomorKamar = $pendaftaran->kamar->nomor_kamar ?? '';
                    $namaAsrama = $pendaftaran->kamar->asrama->nama ?? '';
                    $kamar = $nomorKamar && $namaAsrama ? "{$nomorKamar} ({$namaAsrama})" : '';
                    // Escape the combined kamar value
                    $kamar = $this->escapeForCSV($kamar);
                }

                fputcsv($output, [
                    $no,
                    $nama,
                    $email,
                    $profesi,
                    $instansi,
                    $statusVerifikasi,
                    $kehadiran,
                    $waktuHadir,
                    $kamar,
                ]);

                $no++;
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }

    /**
     * Escape cell content for CSV to prevent injection.
     * Prepends single quote to cells starting with =, +, -, @, TAB, or CR.
     */
    private function escapeForCSV(string $value): string
    {
        // Cegah CSV injection dengan memberi awalan tanda petik tunggal
        if ($value === '') {
            return $value;
        }

        $firstChar = $value[0];
        if (in_array($firstChar, ['=', '+', '-', '@', "\t", "\r"], true)) {
            return '\''.$value;
        }

        return $value;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pelatihans.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePelatihanRequest $request)
    {
        $validated = $request->validated();

        $pelatihan = Pelatihan::create($validated);

        AktivitasLog::catat('tambah_pelatihan', "Menambahkan pelatihan {$pelatihan->nama}", $pelatihan);

        return redirect()
            ->route('pelatihans.index')
            ->with('success', 'Pelatihan berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Pelatihan $pelatihan)
    {
        return view('pelatihans.show', compact('pelatihan'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Pelatihan $pelatihan)
    {
        return view('pelatihans.edit', compact('pelatihan'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePelatihanRequest $request, Pelatihan $pelatihan)
    {
        $validated = $request->validated();
        $statusSebelumnya = $pelatihan->status->value ?? $pelatihan->status;

        $pelatihan->update($validated);

        AktivitasLog::catat('ubah_pelatihan', "Mengubah pelatihan {$pelatihan->nama}", $pelatihan);

        // Kirim notifikasi PelatihanSelesai jika status berubah menjadi 'selesai'
        if ($statusSebelumnya !== 'selesai' && $pelatihan->status === StatusPelatihan::Selesai) {
            // Ambil semua pendaftaran yang diverifikasi dan hadir
            $pendaftaranYangBisaDiterima = $pelatihan->pendaftarans()
                ->where('status_verifikasi', 'diverifikasi')
                ->whereNotNull('hadir_at')
                ->get();

            // Kirim notifikasi ke masing-masing peserta
            foreach ($pendaftaranYangBisaDiterima as $pendaftaran) {
                $pendaftaran->peserta->notify(new PelatihanSelesai($pendaftaran));
            }
        }

        return redirect()
            ->route('pelatihans.index')
            ->with('success', 'Pelatihan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pelatihan $pelatihan)
    {
        $namaPelatihan = $pelatihan->nama;
        $pelatihan->delete();

        AktivitasLog::catat('hapus_pelatihan', "Menghapus pelatihan {$namaPelatihan}", $pelatihan);

        return redirect()
            ->route('pelatihans.index')
            ->with('success', 'Pelatihan berhasil dihapus.');
    }

    /**
     * Show the presensi form for a pelatihan (panitia only).
     */
    public function presensi(Pelatihan $pelatihan)
    {
        // Hitung peserta yang sudah hadir
        $hadir_count = $pelatihan->pendaftarans()
            ->where('status_verifikasi', 'diverifikasi')
            ->whereNotNull('hadir_at')
            ->count();

        // Total peserta yang diverifikasi
        $total_count = $pelatihan->pendaftarans()
            ->where('status_verifikasi', 'diverifikasi')
            ->count();

        // Daftar peserta yang hadir (50 terbaru, urut hadir_at DESC)
        $hadir_list = $pelatihan->pendaftarans()
            ->where('status_verifikasi', 'diverifikasi')
            ->whereNotNull('hadir_at')
            ->orderByDesc('hadir_at')
            ->limit(50)
            ->get()
            ->map(function ($pendaftaran) {
                return [
                    'id' => $pendaftaran->id,
                    'nama' => $pendaftaran->data_diri['nama'] ?? 'Peserta',
                    'hadir_at' => $pendaftaran->hadir_at->format('H:i'),
                ];
            });

        return view('pelatihans.presensi', compact('pelatihan', 'hadir_count', 'total_count', 'hadir_list'));
    }

    /**
     * Show the dashboard rekap for panitia.
     */
    public function dashboard()
    {
        // 1. Hitung pending verifikasi
        $total_pending = Pendaftaran::where('status_verifikasi', 'pending')->count();

        // Temukan pelatihan dengan pending terbanyak (hanya yang pending_count > 0)
        // Gunakan metode PHP sort untuk menghindari HAVING clause di SQLite
        $pelatihan_pending_terbanyak = Pelatihan::withCount([
            'pendaftarans as pending_count' => fn ($q) => $q->where('status_verifikasi', 'pending'),
        ])
            ->get()
            ->filter(fn ($p) => $p->pending_count > 0)
            ->sortByDesc('pending_count')
            ->first();

        // 2. Antrian verifikasi: 5 pendaftaran pending terlama
        $antrian_verifikasi = Pendaftaran::where('status_verifikasi', 'pending')
            ->with(['peserta', 'pelatihan'])
            ->oldest('created_at')
            ->limit(5)
            ->get();

        // 3. Pelatihan dibuka dengan 0 pendaftar atau >=90% kuota
        $pelatihan_perhatian = Pelatihan::where('status', 'dibuka')
            ->withCount([
                'pendaftarans as total' => fn ($q) => $q->where('status_verifikasi', 'diverifikasi'),
            ])
            ->get()
            ->filter(function ($p) {
                $pendaftar = $p->total;

                return $pendaftar === 0 || ($p->kuota > 0 && $pendaftar / $p->kuota >= 0.9);
            })
            ->count();

        // 4. Pelatihan selesai tanpa TIYAS
        $pelatihan_tanpa_tiyas = Pelatihan::query()->sudahSelesai()
            ->whereDoesntHave('pendaftarans.tiyasResponse')
            ->count();

        // 4b. Asrama pending
        $asrama_pending = Pendaftaran::where('butuh_asrama', true)
            ->where('status_verifikasi', 'pending')
            ->count();

        // Temukan pelatihan dengan pending asrama terbanyak (hanya yang > 0)
        // Gunakan metode PHP sort untuk menghindari HAVING clause di SQLite
        $pelatihan_asrama_terbanyak = Pelatihan::withCount([
            'pendaftarans as pending_asrama_count' => fn ($q) => $q->where('butuh_asrama', true)->where('status_verifikasi', 'pending'),
        ])
            ->get()
            ->filter(fn ($p) => $p->pending_asrama_count > 0)
            ->sortByDesc('pending_asrama_count')
            ->first();

        // 5. Daftar 5 pelatihan selesai dengan TIYAS terbaru
        $tiyas_terbaru = Pelatihan::query()->sudahSelesai()
            ->has('tiyasResponses')
            ->withCount('tiyasResponses')
            ->latest('tanggal_selesai')
            ->limit(5)
            ->get();

        // 6. Pelatihan untuk tabel rekap
        $pelatihans = Pelatihan::withCount([
            'pendaftarans as total_pendaftar',
            'pendaftarans as terverifikasi' => fn ($q) => $q->where('status_verifikasi', 'diverifikasi'),
            'pendaftarans as menunggu_verifikasi' => fn ($q) => $q->where('status_verifikasi', 'pending'),
            'pendaftarans as ditolak' => fn ($q) => $q->where('status_verifikasi', 'ditolak'),
            'pendaftarans as hadir' => fn ($q) => $q->whereNotNull('hadir_at'),
            'pendaftarans as butuh_asrama' => fn ($q) => $q->where('butuh_asrama', true),
            'pendaftarans as sertifikat_terunduh' => fn ($q) => $q->whereNotNull('kode_sertifikat'),
        ])->latest()->paginate(10);

        return view('pelatihans.dashboard', compact(
            'pelatihans',
            'total_pending',
            'pelatihan_pending_terbanyak',
            'antrian_verifikasi',
            'pelatihan_perhatian',
            'pelatihan_tanpa_tiyas',
            'asrama_pending',
            'pelatihan_asrama_terbanyak',
            'tiyas_terbaru'
        ));
    }

    /**
     * Show presensi selection page or redirect to single pelatihan presensi.
     * Only shows pelatihan that are currently ongoing (today is between tanggal_mulai and tanggal_selesai).
     */
    public function pilihPresensi()
    {
        $today = today()->toDateString();

        // Get pelatihan yang sedang berlangsung hari ini (status bukan draft)
        $pelatihanBerlangsung = Pelatihan::query()
            ->where('status', '!=', 'draft')
            ->whereDate('tanggal_mulai', '<=', $today)
            ->whereDate('tanggal_selesai', '>=', $today)
            ->withCount([
                'pendaftarans as hadir_count' => fn ($q) => $q->where('status_verifikasi', 'diverifikasi')->whereNotNull('hadir_at'),
                'pendaftarans as diverifikasi_count' => fn ($q) => $q->where('status_verifikasi', 'diverifikasi'),
            ])
            ->get();

        // Jika hanya satu pelatihan, langsung redirect ke halaman presensinya
        if ($pelatihanBerlangsung->count() === 1) {
            return redirect()->route('pelatihan.presensi', $pelatihanBerlangsung->first());
        }

        // Jika tidak ada, tampilkan halaman kosong
        if ($pelatihanBerlangsung->count() === 0) {
            return view('pelatihans.presensi-pilih', [
                'pelatihans' => collect(),
            ]);
        }

        // Jika lebih dari satu, tampilkan halaman daftar
        return view('pelatihans.presensi-pilih', [
            'pelatihans' => $pelatihanBerlangsung,
        ]);
    }

    /**
     * Process the presensi submission (panitia only).
     */
    public function prosesPresensi(Request $request, Pelatihan $pelatihan)
    {
        $validated = $request->validate([
            'kode_presensi' => ['required', 'string', 'max:32'],
        ]);

        $kode = strtoupper(trim($validated['kode_presensi']));

        // Ekstrak logika validasi dan proses ke private method
        $result = $this->validateAndProcessPresensi($kode, $pelatihan);

        // Jika request mengharapkan JSON, kembalikan JSON response
        if ($request->expectsJson()) {
            if ($result['status'] === 'sukses') {
                return response()->json($result, 200);
            } elseif ($result['status'] === 'sudah_hadir') {
                return response()->json($result, 200);
            } elseif ($result['status'] === 'error_validasi') {
                return response()->json([
                    'status' => 'error',
                    'pesan' => $result['pesan'],
                    'nama' => null,
                ], 422);
            } elseif ($result['status'] === 'not_found') {
                return response()->json([
                    'status' => 'error',
                    'pesan' => $result['pesan'],
                    'nama' => null,
                ], 404);
            }
        }

        // Jalur non-JSON: redirect back dengan flash message seperti sebelumnya
        if ($result['status'] === 'sukses') {
            return back()->with('success', $result['pesan']);
        } elseif ($result['status'] === 'sudah_hadir') {
            return back()->with('warning', $result['pesan']);
        } else {
            return back()->with('error', $result['pesan']);
        }
    }

    /**
     * Validate and process presensi logic (ekstrak shared logic).
     * Returns array dengan status, pesan, dan nama (jika ada).
     */
    private function validateAndProcessPresensi(string $kode, Pelatihan $pelatihan): array
    {
        // Cek tanggal presensi: hanya boleh dalam rentang tanggal_mulai s/d tanggal_selesai
        $today = now()->toDateString();
        $tanggal_mulai = $pelatihan->tanggal_mulai->toDateString();
        $tanggal_selesai = $pelatihan->tanggal_selesai->toDateString();

        if ($today < $tanggal_mulai || $today > $tanggal_selesai) {
            $mulai_format = $pelatihan->tanggal_mulai->format('d M Y');
            $selesai_format = $pelatihan->tanggal_selesai->format('d M Y');

            return [
                'status' => 'error_validasi',
                'pesan' => "Presensi hanya bisa dicatat pada tanggal pelatihan ($mulai_format - $selesai_format).",
                'nama' => null,
            ];
        }

        // Cari pendaftaran dengan kode ini untuk pelatihan ini
        $pendaftaran = Pendaftaran::where('kode_presensi', $kode)
            ->where('pelatihan_id', $pelatihan->id)
            ->first();

        // Kalau tidak ketemu di pelatihan ini, cek apakah ada di pelatihan lain
        if (! $pendaftaran) {
            $pendaftaranLain = Pendaftaran::where('kode_presensi', $kode)->first();
            if ($pendaftaranLain) {
                return [
                    'status' => 'not_found',
                    'pesan' => 'Kode presensi tidak berlaku untuk pelatihan ini. Kode ini terdaftar untuk pelatihan lain.',
                    'nama' => null,
                ];
            }

            return [
                'status' => 'not_found',
                'pesan' => 'Kode presensi tidak ditemukan.',
                'nama' => null,
            ];
        }

        $nama = $pendaftaran->data_diri['nama'] ?? 'Peserta';

        // Cek status verifikasi
        if (! $pendaftaran->isDiverifikasi()) {
            return [
                'status' => 'error_validasi',
                'pesan' => 'Pendaftaran belum diverifikasi. Tidak dapat melakukan presensi.',
                'nama' => $nama,
            ];
        }

        // Cek apakah sudah pernah presensi
        if ($pendaftaran->isHadir()) {
            return [
                'status' => 'sudah_hadir',
                'pesan' => "Peserta $nama sudah pernah presensi pada {$pendaftaran->hadir_at->format('d M Y H:i')}.",
                'nama' => $nama,
            ];
        }

        // Tandai hadir
        $pendaftaran->tandaiHadir();

        return [
            'status' => 'sukses',
            'pesan' => "Presensi berhasil! Peserta: $nama.",
            'nama' => $nama,
            'id' => $pendaftaran->id,
            'hadir_at' => $pendaftaran->hadir_at->format('H:i'),
            'hadir_count' => $pelatihan->pendaftarans()
                ->where('status_verifikasi', 'diverifikasi')
                ->whereNotNull('hadir_at')
                ->count(),
        ];
    }
}
