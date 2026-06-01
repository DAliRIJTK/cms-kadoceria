<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
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

    public function down(): void
    {
        Schema::dropIfExists('area_interaktif');
    }
};