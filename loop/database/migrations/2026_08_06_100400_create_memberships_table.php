<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('points_balance')->default(0);
            $table->unsignedInteger('lifetime_points')->default(0);
            $table->string('member_code')->unique();
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            $table->unique(['business_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
