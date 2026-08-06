<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('sector_other')->nullable()->after('sector');
            $table->timestamp('onboarding_completed_at')->nullable()->after('is_active');
            $table->unsignedTinyInteger('branch_count')->nullable()->after('city');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('birth_month')->nullable()->after('birth_date');
            $table->unsignedTinyInteger('birth_day')->nullable()->after('birth_month');
            $table->boolean('profile_completed')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['sector_other', 'onboarding_completed_at', 'branch_count']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['birth_month', 'birth_day', 'profile_completed']);
        });
    }
};
