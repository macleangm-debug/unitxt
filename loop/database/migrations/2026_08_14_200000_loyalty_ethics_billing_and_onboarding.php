<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('intro_seen_at')->nullable()->after('locale');
            $table->timestamp('interests_prompt_seen_at')->nullable()->after('intro_seen_at');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->timestamp('past_due_at')->nullable()->after('trial_ends_at');
        });

        Schema::table('rewards', function (Blueprint $table) {
            $table->timestamp('starts_at')->nullable()->after('max_redemptions_per_member');
            $table->timestamp('ends_at')->nullable()->after('starts_at');
            $table->boolean('is_default')->default(false)->after('ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['intro_seen_at', 'interests_prompt_seen_at']);
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('past_due_at');
        });

        Schema::table('rewards', function (Blueprint $table) {
            $table->dropColumn(['starts_at', 'ends_at', 'is_default']);
        });
    }
};
