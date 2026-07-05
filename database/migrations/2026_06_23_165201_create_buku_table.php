<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buku', function (Blueprint $table) {
            $table->id('id_buku');
            $table->unsignedBigInteger('id_pengelola');
            $table->string('judul_idn', 255)->nullable();
            $table->string('judul_sn', 255)->nullable();
            $table->string('penulis', 100)->nullable();
            $table->string('ilustrator', 100)->nullable();
            $table->string('path_cover', 255)->nullable();
            $table->string('original_pdf_name', 255)->nullable();
            $table->string('status_publikasi', 50)->default('Draft');
            $table->string('zip_bundle_path', 255)->nullable();
            $table->text('deskripsi_idn')->nullable();
            $table->text('deskripsi_sn')->nullable();
            $table->string('warna_primer', 20)->nullable();
            $table->string('warna_sekunder', 20)->nullable();
            $table->timestamps();

            $table->foreign('id_pengelola')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buku');
    }
};