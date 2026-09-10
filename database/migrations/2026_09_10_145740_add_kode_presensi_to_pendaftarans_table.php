<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pendaftarans', function (Blueprint $table) {
            $table->string('kode_presensi', 32)->unique()->nullable()->after('kamar_id');
            $table->timestamp('kode_generated_at')->nullable()->after('kode_presensi');
        });
    }

    public function down(): void
    {
        Schema::table('pendaftarans', function (Blueprint $table) {
            $table->dropColumn(['kode_presensi', 'kode_generated_at']);
        });
    }
};
