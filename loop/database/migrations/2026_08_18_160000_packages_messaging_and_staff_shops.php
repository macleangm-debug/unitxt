<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['shop_id', 'user_id']);
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->timestamp('plan_renews_at')->nullable()->after('trial_ends_at');
            $table->unsignedTinyInteger('plan_interval_months')->default(1)->after('plan_renews_at');
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('has_raffles')->default(false)->after('max_offers');
            $table->boolean('has_sms')->default(false)->after('has_raffles');
        });

        DB::table('plans')->where('key', 'growth')->update(['has_raffles' => true, 'has_sms' => false]);
        DB::table('plans')->where('key', 'scale')->update(['has_raffles' => true, 'has_sms' => true]);

        Schema::create('platform_sender_ids', function (Blueprint $table) {
            $table->id();
            $table->string('code', 11);
            $table->unsignedInteger('yearly_fee')->default(15000);
            $table->string('status', 20)->default('inactive');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique('code');
        });

        $now = now();
        foreach ([
            ['OFFER', 1],
            ['OFA', 2],
            ['DISCOUNT', 3],
            ['PROMOTION', 4],
            ['LOOP', 5],
        ] as [$code, $sort]) {
            DB::table('platform_sender_ids')->insert([
                'code' => $code,
                'yearly_fee' => 15000,
                'status' => 'inactive',
                'sort_order' => $sort,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::create('sender_ids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('platform_sender_id_id')->nullable()->constrained('platform_sender_ids')->nullOnDelete();
            $table->string('code', 11);
            $table->string('kind', 20)->default('custom');
            $table->string('status', 20)->default('pending');
            $table->timestamp('paid_until')->nullable();
            $table->unsignedInteger('yearly_fee')->default(15000);
            $table->timestamps();
            $table->index(['business_id', 'status']);
        });

        Schema::create('member_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('member_group_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['member_group_id', 'user_id']);
        });

        Schema::create('message_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sender_id_id')->nullable()->constrained('sender_ids')->nullOnDelete();
            $table->string('sender_code', 11)->nullable();
            $table->text('body');
            $table->string('audience', 40)->default('all');
            $table->json('audience_meta')->nullable();
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('cost')->default(0);
            $table->string('currency', 8)->default('TZS');
            $table->string('status', 20)->default('draft');
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('payment_intent_id')->nullable()->constrained('payment_intents')->nullOnDelete();
            $table->string('sector')->nullable();
            $table->string('template_key')->nullable();
            $table->string('purpose', 40)->default('member_sms');
            $table->timestamps();
        });

        Schema::create('sms_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('audience', 20)->default('business');
            $table->text('body_en');
            $table->text('body_sw')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        foreach ([
            [
                'key' => 'subscription_renewal',
                'name' => 'Subscription renewal',
                'body_en' => 'Hi :name, your Loop plan renews soon. Keep the till looping — renew in Settings → Billing.',
                'body_sw' => 'Habari :name, mpango wako wa Loop unakaribia kuisha. Endelea kwenye Mipangilio → Malipo.',
            ],
            [
                'key' => 'marketing_tips',
                'name' => 'Marketing tips',
                'body_en' => 'Loop tip: a birthday bonus and one live offer bring members back this week. Open Campaigns to set them.',
                'body_sw' => 'Kidokezo: bonus ya siku ya kuzaliwa na ofa moja hai hurudisha wanachama wiki hii. Fungua Kampeni.',
            ],
            [
                'key' => 'sales_nudge',
                'name' => 'Sales nudge',
                'body_en' => 'Quiet till? A short product-push campaign on your best seller usually lifts visits within days.',
                'body_sw' => 'Till imetulia? Kampeni fupi ya bidhaa maarufu mara nyingi huongeza ziara ndani ya siku chache.',
            ],
        ] as $row) {
            DB::table('sms_templates')->insert([
                ...$row,
                'audience' => 'business',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_templates');
        Schema::dropIfExists('message_broadcasts');
        Schema::dropIfExists('member_group_user');
        Schema::dropIfExists('member_groups');
        Schema::dropIfExists('sender_ids');
        Schema::dropIfExists('platform_sender_ids');

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['has_raffles', 'has_sms']);
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['plan_renews_at', 'plan_interval_months']);
        });

        Schema::dropIfExists('shop_user');
    }
};
