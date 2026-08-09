<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_intents', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('purpose'); // plan_upgrade|affiliate_payout|test
            $table->string('plan_key')->nullable();
            $table->unsignedInteger('amount');
            $table->string('currency', 3)->default('TZS');
            $table->string('phone');
            $table->string('country', 2)->default('TZ');
            $table->string('provider')->default('payin');
            $table->string('provider_ref')->nullable()->index();
            $table->string('status')->default('pending'); // pending|processing|paid|failed|cancelled
            $table->string('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_intents');
    }
};
