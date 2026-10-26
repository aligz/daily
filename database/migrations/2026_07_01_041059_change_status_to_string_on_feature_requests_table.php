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
        if (! $this->usesMysql()) {
            return;
        }

        DB::statement("ALTER TABLE feature_requests MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'new'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! $this->usesMysql()) {
            return;
        }

        DB::statement("ALTER TABLE feature_requests MODIFY COLUMN status ENUM('new','planning','development','done','released') NOT NULL DEFAULT 'new'");
    }

    /**
     * SQLite and PostgreSQL do not support MODIFY COLUMN. SQLite already stores
     * the enum as a varchar (with a check constraint covering the same values),
     * so there is nothing to change there for the current status set.
     */
    private function usesMysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }
};
