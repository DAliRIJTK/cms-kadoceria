<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audio_latar', function (Blueprint $table) {
            $table->id('id_audio_latar');
            $table->string('nama_audio', 100);
            $table->string('path_file', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audio_latar');
    }
};