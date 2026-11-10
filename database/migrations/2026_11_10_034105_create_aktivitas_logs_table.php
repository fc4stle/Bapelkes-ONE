<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('aktivitas_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'user_id')
                ->nullable()
                ->nullOnDelete()
                ->comment('Pelaku aktivitas');
            $table->string('aksi', 60)->comment('Jenis aksi (cth: "verifkasi_pendaftaran", "kirim_pengumuman")');
            $table->string('deskripsi', 255)->comment('Ringkasan deskriptif aksi');
            $table->string('subjek_tipe')->nullable()->comment('Nama model subjek (cth: "Pelatihan", "User")');
            $table->unsignedBigInteger('subjek_id')->nullable()->comment('ID subjek (foreign key dinamis)');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aktivitas_logs');
    }
};
