<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sector_search_misses', function (Blueprint $table) {
            $table->id();
            $table->string('query_key', 80)->unique();
            $table->string('query', 80);
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sector_search_misses');
    }
};
