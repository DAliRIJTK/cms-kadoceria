<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pengelola', function (Blueprint $table) {
            $table->id('id_pengelola');
            $table->string('nama_pengelola', 100);
            $table->string('username', 100)->unique();
            $table->string('password');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengelola');
    }
};