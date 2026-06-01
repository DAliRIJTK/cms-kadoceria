<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('halaman', function (Blueprint $table) {
            $table->id('id_halaman');
            $table->unsignedBigInteger('id_buku');
            $table->unsignedBigInteger('id_audio_latar')->nullable();
            $table->integer('nomor_halaman');
            $table->string('path_gambar', 255)->nullable();
            $table->integer('panjang_halaman')->nullable();
            $table->integer('lebar_halaman')->nullable();
            $table->string('narasi_indo', 255)->nullable();
            $table->string('narasi_sunda', 255)->nullable();
            $table->timestamps();

            $table->foreign('id_buku')
                  ->references('id_buku')
                  ->on('buku')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->foreign('id_audio_latar')
                  ->references('id_audio_latar')
                  ->on('audio_latar')
                  ->onDelete('set null')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('halaman');
    }
};