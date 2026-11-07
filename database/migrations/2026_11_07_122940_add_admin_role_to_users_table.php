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
            // PostgreSQL: drop constraint, ubah ke string dengan default
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 20)->default('peserta')->change();
            });
            // Tambahkan constraint baru dengan 3 role
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('peserta', 'panitia', 'admin'))");
        } else {
            // SQLite: ubah ke string dengan default
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 20)->default('peserta')->change();
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
            // Ubah role='admin' menjadi 'panitia' agar tidak gagal saat revert ke enum 2 role
            DB::table('users')->where('role', 'admin')->update(['role' => 'panitia']);

            // PostgreSQL: drop constraint, kembali ke enum 2 role dengan default
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['peserta', 'panitia'])->default('peserta')->change();
            });
        } else {
            // SQLite: ubah role='admin' menjadi 'panitia', kembali ke enum 2 role dengan default
            DB::table('users')->where('role', 'admin')->update(['role' => 'panitia']);
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['peserta', 'panitia'])->default('peserta')->change();
            });
        }
    }
};
