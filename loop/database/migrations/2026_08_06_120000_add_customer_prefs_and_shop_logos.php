<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('country', 2)->nullable()->after('country_code');
            $table->string('city')->nullable()->after('country');
            $table->json('interests')->nullable()->after('city');
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['country', 'city', 'interests']);
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn('logo_path');
        });
    }
};
