<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('earn'); // earn, birthday, welcome
            $table->text('description')->nullable();
            // Earn rule: every spend_step currency units => points_per_step
            $table->unsignedInteger('spend_step')->nullable(); // e.g. 1000 TZS
            $table->unsignedInteger('points_per_step')->nullable(); // e.g. 2 points
            $table->unsignedInteger('bonus_points')->default(0);
            $table->unsignedInteger('max_earns_per_day')->nullable();
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('template_key')->nullable();
            $table->timestamps();
        });

        Schema::create('campaign_shop', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->unique(['campaign_id', 'shop_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_shop');
        Schema::dropIfExists('campaigns');
    }
};
