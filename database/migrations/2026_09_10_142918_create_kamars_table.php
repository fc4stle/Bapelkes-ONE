<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kamars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asrama_id')->constrained()->cascadeOnDelete();
            $table->string('nomor_kamar');
            $table->unsignedInteger('kapasitas')->default(1);
            $table->timestamps();

            $table->unique(['asrama_id', 'nomor_kamar']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kamars');
    }
};
