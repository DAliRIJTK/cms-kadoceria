<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Convert ENUM type column to VARCHAR to allow all values
     */
    public function up(): void
    {
        if (!Schema::hasTable('audios')) {
            return;
        }

        $connection = DB::connection()->getDriverName();

        if ($connection === 'pgsql') {
            try {
                // First remove any constraints on the enum
                DB::statement("ALTER TABLE audios DROP CONSTRAINT IF EXISTS audios_type_check");
                
                // Convert enum to text
                DB::statement("ALTER TABLE audios ALTER COLUMN type TYPE VARCHAR(50)");
            } catch (\Throwable $e) {
                \Log::warning('Audio type migration warning: ' . $e->getMessage());
            }
        } else if ($connection === 'mysql') {
            // MySQL: Update enum to allow new values
            try {
                DB::statement("ALTER TABLE audios MODIFY COLUMN type VARCHAR(50)");
            } catch (\Throwable $e) {
                \Log::warning('Audio type migration warning: ' . $e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't revert - new flexibility is better
    }
};
