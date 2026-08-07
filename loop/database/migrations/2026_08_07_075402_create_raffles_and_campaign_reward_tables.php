<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_reward', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reward_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['campaign_id', 'reward_id']);
        });

        Schema::create('raffles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('prize_name');
            $table->string('prize_type')->default('custom');
            $table->decimal('prize_value', 12, 2)->nullable();
            $table->unsignedInteger('winners_count')->default(1);
            $table->string('frequency')->default('once'); // once, weekly, monthly, yearly
            $table->date('draw_at');
            $table->unsignedInteger('claim_days')->default(7);
            $table->string('status')->default('scheduled'); // scheduled, live, completed, cancelled
            $table->timestamp('reminded_at')->nullable();
            $table->timestamp('drawn_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('raffle_winners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raffle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('membership_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('draw_order')->default(1);
            $table->string('status')->default('pending'); // pending, contacted, claimed, expired
            $table->timestamp('drawn_at')->nullable();
            $table->timestamp('claim_by')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raffle_winners');
        Schema::dropIfExists('raffles');
        Schema::dropIfExists('campaign_reward');
    }
};
