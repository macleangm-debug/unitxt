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
            $table->string('country', 2)->default('TZ')->after('key');
        });

        DB::table('plans')->whereNull('country')->orWhere('country', '')->update(['country' => 'TZ']);

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('plans', function (Blueprint $table) {
                $table->dropUnique('plans_key_unique');
            });
        } else {
            Schema::table('plans', function (Blueprint $table) {
                $table->dropUnique(['key']);
            });
        }

        Schema::table('plans', function (Blueprint $table) {
            $table->unique(['key', 'country']);
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropUnique(['key', 'country']);
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('country');
            $table->unique('key');
        });
    }
};
