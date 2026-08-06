<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('points_cost');
            $table->string('reward_type')->default('percent_off'); // percent_off, fixed_off, free_item, custom
            $table->decimal('reward_value', 10, 2)->nullable(); // e.g. 5 for 5%
            $table->unsignedInteger('stock')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('membership_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount_spent', 12, 2)->default(0);
            $table->unsignedInteger('points_earned')->default(0);
            $table->foreignId('reward_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('points_redeemed')->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->string('receipt_ref')->nullable();
            $table->string('channel')->default('in_store'); // in_store, phone_order
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'shop_id', 'created_at']);
            $table->index(['recorded_by', 'created_at']);
        });

        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type'); // earn, redeem, adjust
            $table->integer('points');
            $table->unsignedInteger('balance_after');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reward_id')->constrained()->cascadeOnDelete();
            $table->foreignId('membership_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('points_spent');
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->string('status')->default('applied');
            $table->timestamps();
        });

        Schema::create('phone_otps', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 8);
            $table->string('phone', 32);
            $table->string('code', 10);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['country_code', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_otps');
        Schema::dropIfExists('redemptions');
        Schema::dropIfExists('point_transactions');
        Schema::dropIfExists('visits');
        Schema::dropIfExists('rewards');
    }
};
