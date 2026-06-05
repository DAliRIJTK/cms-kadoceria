<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('buku', function (Blueprint $table) {
            $table->id('id_buku');
            $table->unsignedBigInteger('id_pengelola');
            $table->string('judul_idn', 255)->nullable();
            $table->string('judul_sn', 255)->nullable();
            $table->string('nama_folder', 255)->nullable();
            $table->string('penulis', 100)->nullable();
            $table->string('ilustrator', 100)->nullable();
            $table->string('path_cover', 255)->nullable();
            $table->string('status_publikasi', 50)->default('Draft'); // Mengakomodasi BR-02, BR-16, FR-23
            $table->text('deskripsi_idn')->nullable();
            $table->text('deskripsi_sn')->nullable();
            $table->string('warna_primer', 7)->nullable();   // Menyimpan hex warna (ex: #35B1FF)
            $table->string('warna_sekunder', 7)->nullable(); // Menyimpan hex warna (ex: #80CFFF)
            $table->timestamps();

            $table->foreign('id_pengelola')
                  ->references('id_pengelola')
                  ->on('pengelola')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buku');
    }
};