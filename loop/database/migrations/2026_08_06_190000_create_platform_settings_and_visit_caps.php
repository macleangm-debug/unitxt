<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value');
            $table->timestamps();
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('max_monthly_visits')->nullable()->after('max_members');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->json('referral_milestones_applied')->nullable()->after('referral_credit_months');
        });

        DB::table('platform_settings')->insert([
            'key' => 'referral_program',
            'value' => json_encode([
                'goal_count' => 3,
                'referrer_months_per_referral' => 1,
                'referrer_discount_percent' => 50,
                'referred_extra_trial_days' => 30,
                'referred_bonus_months' => 1,
                'milestones' => [
                    ['count' => 3, 'bonus_months' => 2],
                    ['count' => 5, 'bonus_months' => 3],
                ],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('referral_milestones_applied');
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('max_monthly_visits');
        });
        Schema::dropIfExists('platform_settings');
    }
};
