<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exception_hits', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint', 40)->unique();
            $table->string('method', 12);
            $table->string('path', 512);
            $table->string('url', 1024);
            $table->string('route_name', 160)->nullable();
            $table->unsignedSmallInteger('status_code');
            $table->string('exception_class', 191);
            $table->text('message');
            $table->string('file', 512)->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->text('sample_trace')->nullable();
            $table->unsignedInteger('hits')->default(1);
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamp('resolved_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exception_hits');
    }
};
