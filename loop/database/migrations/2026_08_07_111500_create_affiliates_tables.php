<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('pin_hash')->nullable()->after('password');
        });

        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('country_code', 8);
            $table->string('country', 2)->default('TZ');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('id_type'); // national_id, passport, drivers_license, voter_id
            $table->string('id_number');
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->string('payout_phone')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('tracking_code', 24)->nullable()->unique();
            $table->string('promo_code', 16)->nullable()->unique();
            $table->string('status')->default('pending'); // pending, approved, rejected, active, suspended
            $table->text('decision_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();

            $table->unique(['country_code', 'phone']);
        });

        Schema::create('affiliate_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('code_used', 16);
            $table->string('status')->default('pending'); // pending, qualified, commissioned, paid
            $table->unsignedInteger('plan_amount')->default(0);
            $table->unsignedInteger('discount_amount')->default(0);
            $table->unsignedInteger('net_amount')->default(0);
            $table->unsignedTinyInteger('commission_percent')->default(10);
            $table->unsignedInteger('commission_amount')->default(0);
            $table->timestamp('qualified_at')->nullable();
            $table->timestamp('attribution_ends_at')->nullable();
            $table->timestamps();

            $table->unique('business_id');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->foreignId('referred_by_affiliate_id')->nullable()->after('referred_by_business_id')->constrained('affiliates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by_affiliate_id');
        });
        Schema::dropIfExists('affiliate_referrals');
        Schema::dropIfExists('affiliates');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('pin_hash');
        });
    }
};
