<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Division users submit through the public form without an account, and the
 * team only sets priority/deadline later during triage. All three columns have
 * to tolerate null for that flow; internal requests keep filling them in.
 *
 * Rolling back fails if rows with null values still exist, which is the
 * intended behaviour — clean those up before reverting.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feature_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->date('deadline')->nullable()->change();
            $table->string('priority', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('feature_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->date('deadline')->nullable(false)->change();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])
                ->default('medium')
                ->nullable(false)
                ->change();
        });
    }
};
