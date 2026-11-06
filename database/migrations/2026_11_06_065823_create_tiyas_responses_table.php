<?php

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
        Schema::create('tiyas_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendaftaran_id')->unique()->constrained('pendaftarans')->cascadeOnDelete();
            $table->json('identitas'); // pilihan dari config('tiyas.identitas')
            $table->json('jawaban'); // key = kode butir (e.g., "TC1"), value = 1-4
            $table->text('catatan_supervisor')->nullable();
            $table->text('catatan_rekan_kerja')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiyas_responses');
    }
};
