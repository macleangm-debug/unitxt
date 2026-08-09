<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('in_app_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
            $table->string('audience', 32)->default('owner'); // owner|customer|affiliate
            $table->string('type', 64);
            $table->string('dedupe_key', 120);
            $table->string('title_key');
            $table->string('body_key');
            $table->json('params')->nullable();
            $table->string('cta_key')->nullable();
            $table->string('url')->nullable();
            $table->string('tone', 24)->default('mint');
            $table->timestamp('read_at')->nullable();
            $table->date('for_date')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'type', 'dedupe_key', 'for_date'], 'in_app_notifications_dedupe');
            $table->index(['user_id', 'read_at']);
        });

        if (! DB::table('platform_settings')->where('key', 'feature_flags')->exists()) {
            DB::table('platform_settings')->insert([
                'key' => 'feature_flags',
                'value' => json_encode([
                    'pay_with_points' => true,
                    'premium_clients' => true,
                    'owner_daily_digest' => true,
                    'customer_unlock_hints' => true,
                    'birthday_campaigns' => true,
                    'welcome_campaigns' => true,
                    'streak_campaigns' => true,
                    'featured_product' => true,
                    'raffles' => true,
                    'content_studio' => true,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('in_app_notifications');
        DB::table('platform_settings')->where('key', 'feature_flags')->delete();
    }
};
