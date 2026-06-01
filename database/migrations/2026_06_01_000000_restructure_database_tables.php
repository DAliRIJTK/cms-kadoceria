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
        // Drop old tables (dropIfExists will handle foreign keys automatically)
        Schema::dropIfExists('audios');
        Schema::dropIfExists('bounding_boxes');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('books');

        // Create new audio_latar table
        Schema::create('audio_latar', function (Blueprint $table) {
            $table->id('id_audio_latar');
            $table->string('nama_audio', 100);
            $table->string('path_file', 255);
            $table->timestamps();
        });

        // Create new buku table
        Schema::create('buku', function (Blueprint $table) {
            $table->id('id_buku');
            $table->unsignedBigInteger('id_pengelola');
            $table->string('judul_idn', 255)->nullable();
            $table->string('judul_sn', 255)->nullable();
            $table->string('penulis', 100)->nullable();
            $table->string('ilustrator', 100)->nullable();
            $table->string('path_cover', 255)->nullable();
            $table->string('status_publikasi', 50)->default('Draft');
            $table->text('deskripsi_idn')->nullable();
            $table->text('deskripsi_sn')->nullable();
            $table->string('warna_primer', 7)->nullable();
            $table->string('warna_sekunder', 7)->nullable();
            $table->timestamps();

            $table->foreign('id_pengelola')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });

        // Create new halaman table
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

        // Create new area_interaktif table
        Schema::create('area_interaktif', function (Blueprint $table) {
            $table->id('id_area');
            $table->unsignedBigInteger('id_halaman');
            $table->integer('x');
            $table->integer('y');
            $table->integer('lebar_area');
            $table->integer('panjang_area');
            $table->string('audio_indo', 255)->nullable();
            $table->string('audio_sunda', 255)->nullable();
            $table->timestamps();

            $table->foreign('id_halaman')
                  ->references('id_halaman')
                  ->on('halaman')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop new tables in reverse order
        Schema::dropIfExists('area_interaktif');
        Schema::dropIfExists('halaman');
        Schema::dropIfExists('buku');
        Schema::dropIfExists('audio_latar');
    }
};
