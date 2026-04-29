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
        Schema::table('audios', function (Blueprint $table) {
            // Add label column if it doesn't exist
            if (!Schema::hasColumn('audios', 'label')) {
                $table->string('label')->nullable()->after('type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audios', function (Blueprint $table) {
            if (Schema::hasColumn('audios', 'label')) {
                $table->dropColumn('label');
            }
        });
    }
};
