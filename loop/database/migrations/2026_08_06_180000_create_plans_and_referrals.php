<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('tagline')->nullable();
            $table->unsignedInteger('price_monthly')->default(0);
            $table->string('currency', 8)->default('TZS');
            $table->unsignedSmallInteger('max_shops')->nullable();
            $table->unsignedInteger('max_members')->nullable();
            $table->boolean('is_public')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('features')->nullable();
            $table->timestamps();
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->string('plan_key')->default('free')->after('onboarding_completed_at');
            $table->string('referral_code', 16)->nullable()->unique()->after('plan_key');
            $table->foreignId('referred_by_business_id')->nullable()->after('referral_code')
                ->constrained('businesses')->nullOnDelete();
            $table->string('billing_status', 32)->default('trialing')->after('referred_by_business_id');
            $table->timestamp('trial_ends_at')->nullable()->after('billing_status');
            $table->unsignedTinyInteger('referral_discount_percent')->default(0)->after('trial_ends_at');
            $table->unsignedSmallInteger('referral_credit_months')->default(0)->after('referral_discount_percent');
        });

        Schema::create('business_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('referred_business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('code_used', 16);
            $table->string('status', 32)->default('pending'); // pending, qualified, rewarded
            $table->timestamp('qualified_at')->nullable();
            $table->timestamp('rewarded_at')->nullable();
            $table->string('reward_type', 32)->nullable(); // free_month, percent_off
            $table->unsignedInteger('reward_value')->nullable();
            $table->timestamps();

            $table->unique('referred_business_id');
            $table->index(['referrer_business_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_referrals');

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by_business_id');
            $table->dropColumn([
                'plan_key',
                'referral_code',
                'billing_status',
                'trial_ends_at',
                'referral_discount_percent',
                'referral_credit_months',
            ]);
        });

        Schema::dropIfExists('plans');
    }
};
