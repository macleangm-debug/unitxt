<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('hotline', 40)->nullable()->after('city');
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->unsignedSmallInteger('streak_target')->nullable()->after('bonus_points');
            $table->string('streak_period', 16)->nullable()->after('streak_target');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('hotline');
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['streak_target', 'streak_period']);
        });
    }
};
