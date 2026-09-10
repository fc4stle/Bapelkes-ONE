<?php

namespace Database\Seeders;

use App\Models\Asrama;
use App\Models\Kamar;
use Illuminate\Database\Seeder;

class AsramaKamarSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['nama' => 'Kunthi', 'kapasitas' => 40, 'jumlah_kamar' => 20],
            ['nama' => 'Srikandi', 'kapasitas' => 40, 'jumlah_kamar' => 20],
            ['nama' => 'Pandu', 'kapasitas' => 20, 'jumlah_kamar' => 10],
        ];

        foreach ($data as $asramaData) {
            $asrama = Asrama::create([
                'nama' => $asramaData['nama'],
                'kapasitas' => $asramaData['kapasitas'],
            ]);

            for ($i = 1; $i <= $asramaData['jumlah_kamar']; $i++) {
                Kamar::create([
                    'asrama_id' => $asrama->id,
                    'nomor_kamar' => $asramaData['nama'].'-'.str_pad($i, 3, '0', STR_PAD_LEFT),
                    'kapasitas' => 1,
                ]);
            }
        }
    }
}
