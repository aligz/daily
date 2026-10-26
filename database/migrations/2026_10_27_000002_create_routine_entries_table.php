<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routine_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routine_id')->constrained()->cascadeOnDelete();
            $table->date('check_date');
            $table->string('status', 16);
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->unique(['routine_id', 'check_date']);
            $table->index(['check_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routine_entries');
    }
};
