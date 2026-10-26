<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->float('position')->default(0);
            $table->date('starts_on');
            $table->date('archived_on')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'archived_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routines');
    }
};
