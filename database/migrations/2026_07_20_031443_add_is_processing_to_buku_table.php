<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buku', function (Blueprint $table) {
            // Default false agar buku lama tidak terkunci
            $table->boolean('is_processing')->default(false)->after('pdf_hash'); 
        });
    }

    public function down(): void
    {
        Schema::table('buku', function (Blueprint $table) {
            $table->dropColumn('is_processing');
        });
    }
};