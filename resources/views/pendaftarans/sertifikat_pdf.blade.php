<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sertifikat - {{ $pendaftaran->pelatihan->nama }}</title>
    <style>
        @page {
            margin: 0;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            margin: 0;
            padding: 0;
            background: #fff;
        }
        .sertifikat {
            width: 297mm;
            height: 210mm;
            padding: 40px;
            box-sizing: border-box;
            position: relative;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border: 20px solid #4f46e5;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 28px;
            color: #1e293b;
            margin: 0;
            letter-spacing: 2px;
        }
        .header h2 {
            font-size: 36px;
            color: #4f46e5;
            margin: 10px 0;
            text-transform: uppercase;
        }
        .content {
            text-align: center;
            margin: 40px 0;
        }
        .content p {
            font-size: 16px;
            color: #475569;
            line-height: 1.8;
        }
        .nama-peserta {
            font-size: 32px;
            font-weight: bold;
            color: #1e293b;
            margin: 20px 0;
            border-bottom: 2px solid #4f46e5;
            display: inline-block;
            padding-bottom: 10px;
        }
        .pelatihan {
            font-size: 22px;
            font-weight: bold;
            color: #4f46e5;
            margin: 15px 0;
        }
        .footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 50px;
            padding: 0 40px;
        }
        .footer .kode {
            font-size: 10px;
            color: #94a3b8;
            font-family: monospace;
        }
        .footer .tanggal {
            font-size: 12px;
            color: #64748b;
        }
        .stempel {
            text-align: center;
            opacity: 0.6;
        }
        .stempel p {
            font-size: 12px;
            color: #4f46e5;
            margin: 0;
        }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 100px;
            color: rgba(79, 70, 229, 0.03);
            pointer-events: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="sertifikat">
        <div class="watermark">BAPELKES</div>
        <div class="header">
            <h1>BADAN PELATIHAN KESEHATAN</h1>
            <h2>Sertifikat Penyelesaian</h2>
            <p style="color: #64748b; font-size: 14px;">Dinas Kesehatan Daerah Istimewa Yogyakarta</p>
        </div>
        <div class="content">
            <p>Diberikan kepada:</p>
            <div class="nama-peserta">{{ $pendaftaran->data_diri['nama'] ?? '-' }}</div>
            <p>telah berhasil menyelesaikan pelatihan</p>
            <div class="pelatihan">{{ $pendaftaran->pelatihan->nama }}</div>
            <p>
                diselenggarakan pada tanggal
                <strong>{{ $pendaftaran->pelatihan->tanggal_mulai->format('d M Y') }}</strong>
                sampai
                <strong>{{ $pendaftaran->pelatihan->tanggal_selesai->format('d M Y') }}</strong>
                di {{ $pendaftaran->pelatihan->lokasi }}
            </p>
        </div>
        <div class="footer">
            <div class="kode">
                <p>No. Sertifikat: {{ $pendaftaran->kode_sertifikat }}</p>
                <p>Verifikasi: {{ url('/verify/' . $pendaftaran->kode_sertifikat) }}</p>
            </div>
            <div class="stempel">
                <p>__________</p>
                <p style="font-size: 11px; margin-top: 5px;">Kepala Bapelkes</p>
            </div>
            <div class="tanggal">
                <p>Diterbitkan: {{ $pendaftaran->sertifikat_generated_at->format('d M Y') }}</p>
            </div>
        </div>
    </div>
</body>
</html>
