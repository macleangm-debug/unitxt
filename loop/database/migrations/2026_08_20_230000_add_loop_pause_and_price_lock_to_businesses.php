<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->timestamp('paused_at')->nullable()->after('plan_interval_months');
            $table->timestamp('grace_started_at')->nullable()->after('paused_at');
            $table->timestamp('price_locked_until')->nullable()->after('grace_started_at');
            $table->unsignedInteger('price_locked_monthly')->nullable()->after('price_locked_until');
            $table->string('price_locked_plan_key', 32)->nullable()->after('price_locked_monthly');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'paused_at',
                'grace_started_at',
                'price_locked_until',
                'price_locked_monthly',
                'price_locked_plan_key',
            ]);
        });
    }
};
