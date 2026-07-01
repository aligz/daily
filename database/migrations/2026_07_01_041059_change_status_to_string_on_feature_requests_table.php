<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE feature_requests MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'new'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE feature_requests MODIFY COLUMN status ENUM('new','planning','development','done','released') NOT NULL DEFAULT 'new'");
    }
};
