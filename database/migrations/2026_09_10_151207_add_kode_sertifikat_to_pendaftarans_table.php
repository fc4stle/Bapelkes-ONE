<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pendaftarans', function (Blueprint $table) {
            $table->string('kode_sertifikat', 32)->unique()->nullable()->after('hadir_at');
            $table->timestamp('sertifikat_generated_at')->nullable()->after('kode_sertifikat');
        });
    }

    public function down(): void
    {
        Schema::table('pendaftarans', function (Blueprint $table) {
            $table->dropColumn(['kode_sertifikat', 'sertifikat_generated_at']);
        });
    }
};
