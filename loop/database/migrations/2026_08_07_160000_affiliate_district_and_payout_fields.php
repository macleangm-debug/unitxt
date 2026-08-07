<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliates', function (Blueprint $table) {
            $table->string('district')->nullable()->after('city');
            $table->string('payout_method')->nullable()->after('address'); // phone | bank
            $table->string('payout_account_name')->nullable()->after('bank_name');
        });
    }

    public function down(): void
    {
        Schema::table('affiliates', function (Blueprint $table) {
            $table->dropColumn(['district', 'payout_method', 'payout_account_name']);
        });
    }
};
