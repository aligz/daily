<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feature_requests', function (Blueprint $table) {
            $table->text('change_reason')->nullable()->after('description');
            $table->text('proposed_action')->nullable()->after('change_reason');
            $table->text('effect_on_organization')->nullable()->after('proposed_action');
            $table->text('effect_of_not_approving')->nullable()->after('effect_on_organization');
        });
    }

    public function down(): void
    {
        Schema::table('feature_requests', function (Blueprint $table) {
            $table->dropColumn([
                'change_reason',
                'proposed_action',
                'effect_on_organization',
                'effect_of_not_approving',
            ]);
        });
    }
};
