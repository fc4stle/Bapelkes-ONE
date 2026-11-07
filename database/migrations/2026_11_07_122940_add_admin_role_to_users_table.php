<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            // PostgreSQL: drop constraint, ubah ke string dengan constraint baru
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 20)->change();
            });
            // Tambahkan constraint baru dengan 3 role
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('peserta', 'panitia', 'admin'))");
        } else {
            // SQLite: ubah enum ke string (SQLite enum pakai varchar anyway)
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 20)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            // PostgreSQL: drop constraint, kembali ke enum 2 role
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['peserta', 'panitia'])->default('peserta')->change();
            });
        } else {
            // SQLite: kembali ke enum 2 role
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['peserta', 'panitia'])->default('peserta')->change();
            });
        }
    }
};
