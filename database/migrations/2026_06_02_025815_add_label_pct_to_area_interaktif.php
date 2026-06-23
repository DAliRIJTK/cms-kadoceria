<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('area_interaktif', function (Blueprint $table) {
            // Label area (nama objek, misal: "Mata", "Telinga")
            $table->string('label', 255)->nullable()->after('id_halaman');

            // Koordinat persentase untuk responsive overlay
            $table->decimal('x_pct', 8, 4)->default(0)->after('panjang_area');
            $table->decimal('y_pct', 8, 4)->default(0)->after('x_pct');
            $table->decimal('w_pct', 8, 4)->default(0)->after('y_pct');
            $table->decimal('h_pct', 8, 4)->default(0)->after('w_pct');
        });
    }

    public function down(): void
    {
        Schema::table('area_interaktif', function (Blueprint $table) {
            $table->dropColumn(['label', 'x_pct', 'y_pct', 'w_pct', 'h_pct']);
        });
    }
};