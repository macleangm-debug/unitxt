<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rewards', function (Blueprint $table) {
            $table->string('product_name')->nullable()->after('description');
            $table->string('product_sku')->nullable()->after('product_name');
            $table->unsignedInteger('max_redemptions_per_member')->nullable()->after('stock');
        });
    }

    public function down(): void
    {
        Schema::table('rewards', function (Blueprint $table) {
            $table->dropColumn(['product_name', 'product_sku', 'max_redemptions_per_member']);
        });
    }
};
