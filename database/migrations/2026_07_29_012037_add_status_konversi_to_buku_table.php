<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buku', function (Blueprint $table) {
            $table->boolean('status_konversi')->default(false)->after('is_processing');
            $table->string('local_pdf_path')->nullable()->after('pdf_hash');
        });
    }

    public function down(): void
    {
        Schema::table('buku', function (Blueprint $table) {
            $table->dropColumn(['status_konversi', 'local_pdf_path']);
        });
    }
};