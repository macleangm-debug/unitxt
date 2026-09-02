<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loop_back_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('points_snapshot')->default(0);
            $table->boolean('close_to_reward')->default(false);
            $table->boolean('reward_ready')->default(false);
            $table->timestamps();

            $table->unique(['business_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loop_back_requests');
    }
};
