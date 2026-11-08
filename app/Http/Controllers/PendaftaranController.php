<?php

namespace App\Http\Controllers;

use App\Enums\StatusPelatihan;
use App\Models\Kamar;
use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Services\KamarService;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PendaftaranController extends Controller
{
    protected KamarService $kamarService;

    public function __construct(KamarService $kamarService)
    {
        $this->kamarService = $kamarService;
    }

    /**
     * Display a listing of the authenticated user's registrations.
     */
    public function index()
    {
        $pendaftarans = Auth::user()
            ->pendaftarans()
            ->with('pelatihan')
            ->latest()
            ->get();

        return view('pendaftarans.index', compact('pendaftarans'));
    }

    /**
     * Show the registration form for a pelatihan.
     */
    public function create(Pelatihan $pelatihan)
    {
        if ($pelatihan->status !== StatusPelatihan::Dibuka) {
            return back()->with('error', 'Pendaftaran untuk pelatihan ini belum dibuka.');
        }

        if ($pelatihan->isFull()) {
            return back()->with('error', 'Kuota pelatihan ini sudah penuh.');
        }

        if (Auth::user()->pendaftaranPelatihan($pelatihan)) {
            return back()->with('error', 'Anda sudah terdaftar pada pelatihan ini.');
        }

        return view('pendaftarans.create', compact('pelatihan'));
    }

    /**
     * Register the authenticated user to the given pelatihan.
     */
    public function store(Request $request, Pelatihan $pelatihan)
    {
        $user = $request->user();

        if ($pelatihan->status !== StatusPelatihan::Dibuka) {
            return back()->with('error', 'Pendaftaran untuk pelatihan ini belum dibuka.');
        }

        if ($pelatihan->isFull()) {
            return back()->with('error', 'Kuota pelatihan ini sudah penuh.');
        }

        if ($user->pendaftaranPelatihan($pelatihan)) {
            return back()->with('error', 'Anda sudah terdaftar pada pelatihan ini.');
        }

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'string', 'max:16'],
            'kontak' => ['required', 'string', 'max:255'],
            'profesi' => ['required', 'string', 'max:255'],
            'instansi' => ['required', 'string', 'max:255'],
            'surat_tugas' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'dokumen_lain' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'butuh_asrama' => ['nullable', 'boolean'],
            'check_in' => ['required_if:butuh_asrama,true', 'nullable', 'date'],
            'check_out' => ['required_if:butuh_asrama,true', 'nullable', 'date', 'after_or_equal:check_in'],
        ]);

        $suratTugasPath = null;
        if ($request->hasFile('surat_tugas')) {
            $suratTugasPath = $request->file('surat_tugas')->store('surat_tugas', 'supabase');
        }

        $dokumenLainPaths = [];
        if ($request->hasFile('dokumen_lain')) {
            $dokumenLainPaths[] = $request->file('dokumen_lain')->store('dokumen_pendaftar', 'supabase');
        }

        $butuhAsrama = (bool) ($validated['butuh_asrama'] ?? false);
        $checkIn = $validated['check_in'] ?? null;
        $checkOut = $validated['check_out'] ?? null;
        [$kamarId, $pesanAsrama] = $this->alokasikanKamarJikaPerlu(
            $butuhAsrama,
            $checkIn,
            $checkOut,
            'Pendaftaran berhasil dikirim. Kamar {kamar} di {asrama} telah dialokasikan untuk Anda.',
            'Pendaftaran berhasil dikirim, tetapi asrama sudah penuh untuk tanggal tersebut. Anda tetap terdaftar tanpa kamar.'
        );

        Pendaftaran::create([
            'peserta_id' => $user->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'pending',
            'data_diri' => [
                'nama' => $validated['nama'],
                'nik' => $validated['nik'],
                'kontak' => $validated['kontak'],
                'profesi' => $validated['profesi'],
                'instansi' => $validated['instansi'],
            ],
            'dokumen' => json_encode(array_filter([
                'surat_tugas' => $suratTugasPath,
                'dokumen_lain' => $dokumenLainPaths,
            ])),
            'butuh_asrama' => $butuhAsrama,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'kamar_id' => $kamarId,
        ]);

        $successMessage = $pesanAsrama ?? 'Pendaftaran berhasil dikirim, menunggu konfirmasi panitia.';

        return redirect()->route('pendaftarans.index')->with('success', $successMessage);
    }

    /**
     * Show the edit form for the authenticated peserta's own registration.
     */
    public function edit(Pendaftaran $pendaftaran)
    {
        $this->authorize('update', $pendaftaran);

        $pelatihan = $pendaftaran->pelatihan;

        return view('pendaftarans.edit', compact('pendaftaran', 'pelatihan'));
    }

    /**
     * Update the authenticated peserta's own registration (data diri, dokumen, asrama).
     */
    public function updateSelf(Request $request, Pendaftaran $pendaftaran)
    {
        $this->authorize('update', $pendaftaran);

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'string', 'max:16'],
            'kontak' => ['required', 'string', 'max:255'],
            'profesi' => ['required', 'string', 'max:255'],
            'instansi' => ['required', 'string', 'max:255'],
            'surat_tugas' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'dokumen_lain' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'butuh_asrama' => ['nullable', 'boolean'],
            'check_in' => ['required_if:butuh_asrama,true', 'nullable', 'date'],
            'check_out' => ['required_if:butuh_asrama,true', 'nullable', 'date', 'after_or_equal:check_in'],
        ]);

        $dokumen = json_decode($pendaftaran->dokumen ?? '{}', true) ?: [];

        if ($request->hasFile('surat_tugas')) {
            $dokumen['surat_tugas'] = $request->file('surat_tugas')->store('surat_tugas', 'supabase');
        }

        if ($request->hasFile('dokumen_lain')) {
            $dokumen['dokumen_lain'] = [$request->file('dokumen_lain')->store('dokumen_pendaftar', 'supabase')];
        }

        $butuhAsrama = (bool) ($validated['butuh_asrama'] ?? false);
        $checkIn = $validated['check_in'] ?? null;
        $checkOut = $validated['check_out'] ?? null;

        // Lepas alokasi kamar saat ini dulu, supaya kapasitas kamar lama tidak
        // ikut dihitung sebagai "terisi oleh diri sendiri" saat realokasi di bawah.
        if ($pendaftaran->kamar_id) {
            $pendaftaran->update(['kamar_id' => null]);
        }

        [$kamarId, $pesanAsrama] = $this->alokasikanKamarJikaPerlu(
            $butuhAsrama,
            $checkIn,
            $checkOut,
            'Pendaftaran berhasil diperbarui. Kamar {kamar} di {asrama} telah dialokasikan untuk Anda.',
            'Pendaftaran berhasil diperbarui, tetapi asrama sudah penuh untuk tanggal tersebut. Anda tetap terdaftar tanpa kamar.'
        );

        $pendaftaran->update([
            'data_diri' => [
                'nama' => $validated['nama'],
                'nik' => $validated['nik'],
                'kontak' => $validated['kontak'],
                'profesi' => $validated['profesi'],
                'instansi' => $validated['instansi'],
            ],
            'dokumen' => json_encode(array_filter($dokumen)),
            'butuh_asrama' => $butuhAsrama,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'kamar_id' => $kamarId,
        ]);

        $successMessage = $pesanAsrama ?? 'Pendaftaran berhasil diperbarui.';

        return redirect()->route('pendaftarans.index')->with('success', $successMessage);
    }

    /**
     * Cari dan alokasikan kamar jika peserta butuh asrama, atau kembalikan null bila tidak.
     *
     * @return array{0: int|null, 1: string|null} [kamar_id, pesan]
     */
    private function alokasikanKamarJikaPerlu(
        bool $butuhAsrama,
        ?string $checkIn,
        ?string $checkOut,
        string $pesanBerhasil,
        string $pesanPenuh
    ): array {
        if (! $butuhAsrama || ! $checkIn || ! $checkOut) {
            return [null, null];
        }

        $kamar = $this->kamarService->alokasikanKamar($checkIn, $checkOut);

        if (! $kamar) {
            return [null, $pesanPenuh];
        }

        $pesan = str_replace(
            ['{kamar}', '{asrama}'],
            [$kamar->nomor_kamar, $kamar->asrama->nama],
            $pesanBerhasil
        );

        return [$kamar->id, $pesan];
    }

    /**
     * Update the status of a registration.
     */
    public function update(Request $request, Pendaftaran $pendaftaran)
    {
        $validated = $request->validate([
            'status_verifikasi' => ['required', 'in:diverifikasi,ditolak'],
            'catatan' => ['nullable', 'string'],
        ]);

        $pendaftaran->update($validated);

        return back()->with('success', 'Status pendaftaran berhasil diperbarui.');
    }

    /**
     * Verify a registration (panitia only).
     */
    public function verifikasi(Pendaftaran $pendaftaran)
    {
        $this->authorize('verify', $pendaftaran);

        $pendaftaran->verifikasi(Auth::user());

        return back()->with('success', 'Pendaftaran berhasil diverifikasi.');
    }

    /**
     * Reject a registration with a reason (panitia only).
     */
    public function tolak(Request $request, Pendaftaran $pendaftaran)
    {
        $this->authorize('reject', $pendaftaran);

        $validated = $request->validate([
            'alasan_penolakan' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $pendaftaran->tolak(Auth::user(), $validated['alasan_penolakan']);

        return back()->with('success', 'Pendaftaran berhasil ditolak.');
    }

    /**
     * Cancel the authenticated user's own registration.
     */
    public function destroy(Pendaftaran $pendaftaran)
    {
        $this->authorize('delete', $pendaftaran);

        $pendaftaran->delete();

        return back()->with('success', 'Pendaftaran berhasil dibatalkan.');
    }

    /**
     * Show the digital participant card (kartu peserta) for verified registration.
     */
    public function kartu(Pendaftaran $pendaftaran)
    {
        $this->authorize('view', $pendaftaran);

        // Load relationships
        $pendaftaran->load(['pelatihan', 'kamar.asrama', 'verifier']);

        // Generate QR code if verified
        $qrCode = null;
        if ($pendaftaran->isDiverifikasi() && $pendaftaran->kode_presensi) {
            $renderer = new ImageRenderer(
                new RendererStyle(300),
                new SvgImageBackEnd
            );
            $writer = new Writer($renderer);
            $qrCode = $writer->writeString($pendaftaran->kode_presensi);
        }

        return view('pendaftarans.kartu', compact('pendaftaran', 'qrCode'));
    }

    /**
     * Show/download the sertifikat PDF for a verified and attended registration.
     */
    public function sertifikat(Pendaftaran $pendaftaran)
    {
        $this->authorize('view', $pendaftaran);

        // Check eligibility
        if (! $pendaftaran->isDiverifikasi()) {
            return view('pendaftarans.sertifikat_belum', [
                'pendaftaran' => $pendaftaran,
                'alasan' => 'Pendaftaran belum diverifikasi.',
            ]);
        }

        if (! $pendaftaran->isHadir()) {
            return view('pendaftarans.sertifikat_belum', [
                'pendaftaran' => $pendaftaran,
                'alasan' => 'Kehadiran belum tercatat. Sertifikat tersedia setelah peserta melakukan presensi.',
            ]);
        }

        if (! $pendaftaran->isPelatihanSelesai()) {
            return view('pendaftarans.sertifikat_belum', [
                'pendaftaran' => $pendaftaran,
                'alasan' => 'Pelatihan belum selesai. Sertifikat tersedia setelah pelatihan berakhir.',
            ]);
        }

        // Load relationships
        $pendaftaran->load(['pelatihan', 'kamar.asrama', 'verifier']);

        // Generate kode_sertifikat if not exists
        if (! $pendaftaran->isSudahSertifikat()) {
            $pendaftaran->generateKodeSertifikat();
            $pendaftaran->refresh();
        }

        // Generate PDF
        $pdf = \PDF::loadView('pendaftarans.sertifikat_pdf', compact('pendaftaran'));

        $filename = 'Sertifikat_'.Str::slug($pendaftaran->data_diri['nama'] ?? 'peserta').'_'.$pendaftaran->kode_sertifikat.'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Pindahkan penghuni ke kamar lain.
     */
    public function pindahKamar(Request $request, Pendaftaran $pendaftaran)
    {
        // Cek akses
        if (! auth()->user()->isStaf()) {
            abort(403);
        }

        // Validasi pendaftaran tidak boleh ditolak
        if ($pendaftaran->status_verifikasi === 'ditolak') {
            return back()->withErrors(['kamar_id' => 'Tidak dapat memindahkan peserta dengan status pendaftaran ditolak.']);
        }

        // Validasi
        $validated = $request->validate([
            'kamar_id' => 'required|integer|exists:kamars,id',
        ]);

        // Cek apakah pendaftaran butuh asrama
        if (! $pendaftaran->butuh_asrama) {
            return back()->with('error', 'Pendaftaran ini tidak memerlukan asrama.');
        }

        // Cek apakah kamar tujuan sama dengan kamar saat ini
        if ($pendaftaran->kamar_id === (int) $validated['kamar_id']) {
            return back()->withErrors(['kamar_id' => 'Peserta sudah berada di kamar ini.']);
        }

        $kamarTujuan = Kamar::findOrFail($validated['kamar_id']);

        // Update kamar_id dengan transaction dan lock
        try {
            DB::transaction(function () use ($pendaftaran, $kamarTujuan) {
                // Lock kamar tujuan untuk mencegah race condition
                $kamarTujuan = Kamar::lockForUpdate()->findOrFail($kamarTujuan->id);

                // Verifikasi ketersediaan setelah lock, kecuali pendaftaran saat ini
                if (! $kamarTujuan->tersediaUntukTanggal(
                    $pendaftaran->check_in->format('Y-m-d'),
                    $pendaftaran->check_out->format('Y-m-d'),
                    $pendaftaran->id // exclude diri sendiri
                )) {
                    $validator = Validator::make(
                        ['kamar_id' => $pendaftaran->kamar_id],
                        ['kamar_id' => 'required']
                    );
                    $validator->errors()->add('kamar_id', 'Kamar tujuan tidak tersedia untuk rentang tanggal ini.');
                    throw new ValidationException($validator);
                }

                $pendaftaran->update(['kamar_id' => $kamarTujuan->id]);
            });
            
            // Kirim notifikasi SETELAH transaction berhasil
            $pendaftaran->peserta->notify(new \App\Notifications\KamarDipindah($pendaftaran, $kamarTujuan->nomor_kamar));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()
            ->route('panitia.asrama.show', $kamarTujuan->asrama_id)
            ->with('success', 'Penghuni berhasil dipindahkan ke kamar '.$kamarTujuan->nomor_kamar);
    }

    /**
     * Show notifications page for authenticated user
     */
    public function notifikasiIndex(Request $request)
    {
        $notifikasi = Auth::user()
            ->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('notifikasi.index', compact('notifikasi'));
    }

    /**
     * Mark a single notification as read
     */
    public function tandaiDibaca($notification)
    {
        $notif = Auth::user()->notifications()->findOrFail($notification);
        
        $notif->markAsRead();

        return back()->with('success', 'Notifikasi telah ditandai dibaca.');
    }

    /**
     * Mark all notifications as read
     */
    public function tandaiSemuaDibaca(Request $request)
    {
        Auth::user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Semua notifikasi telah ditandai dibaca.');
    }
}
