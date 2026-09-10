<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pendaftarans', function (Blueprint $table) {
            $table->date('check_in')->nullable()->after('butuh_asrama');
            $table->date('check_out')->nullable()->after('check_in');
            $table->foreignId('kamar_id')->nullable()->after('check_out')->constrained('kamars')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pendaftarans', function (Blueprint $table) {
            $table->dropForeign(['kamar_id']);
            $table->dropColumn(['check_in', 'check_out', 'kamar_id']);
        });
    }
};
