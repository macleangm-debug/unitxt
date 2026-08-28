<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('has_games')->default(false)->after('has_sms');
        });

        DB::table('plans')->where('key', 'scale')->update(['has_games' => true]);

        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 24);
            $table->string('name');
            $table->string('status', 24)->default('live');
            $table->string('qualify_mode', 24)->default('spend');
            $table->unsignedInteger('spend_threshold')->nullable();
            $table->unsignedSmallInteger('visit_threshold')->nullable();
            $table->string('play_limit', 24)->default('daily');
            $table->string('win_mode', 24)->default('automatic');
            $table->unsignedSmallInteger('odds_every')->nullable();
            $table->unsignedSmallInteger('spread_count')->nullable();
            $table->string('spread_period', 16)->nullable();
            $table->unsignedInteger('expected_plays')->default(500);
            $table->date('starts_at');
            $table->date('ends_at');
            $table->unsignedSmallInteger('claim_days')->default(7);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['business_id', 'status']);
        });

        Schema::create('game_prizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 24);
            $table->string('name');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('awarded_count')->default(0);
            $table->unsignedInteger('points_value')->nullable();
            $table->unsignedSmallInteger('percent_value')->nullable();
            $table->unsignedInteger('unit_cost')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('game_plays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('membership_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('token', 40)->unique();
            $table->string('status', 24)->default('pending');
            $table->string('outcome', 24)->nullable();
            $table->foreignId('prize_id')->nullable()->constrained('game_prizes')->nullOnDelete();
            $table->timestamp('eligible_at')->nullable();
            $table->timestamp('played_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('claim_by')->nullable();
            $table->timestamps();

            $table->index(['game_id', 'customer_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index(['game_id', 'visit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_plays');
        Schema::dropIfExists('game_prizes');
        Schema::dropIfExists('games');
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('has_games');
        });
    }
};
