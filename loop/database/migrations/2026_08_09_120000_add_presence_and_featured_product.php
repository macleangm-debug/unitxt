<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('presence', 20)->default('physical')->after('branch_count');
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('featured_product_name')->nullable()->after('bonus_points');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('presence');
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('featured_product_name');
        });
    }
};
