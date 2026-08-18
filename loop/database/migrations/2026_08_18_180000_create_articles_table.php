<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('title_en')->nullable();
            $table->string('title_sw')->nullable();
            $table->string('excerpt_en', 500)->nullable();
            $table->string('excerpt_sw', 500)->nullable();
            $table->text('body_en')->nullable();
            $table->text('body_sw')->nullable();
            $table->string('image_path')->nullable();
            $table->string('country', 8)->nullable();
            $table->string('audience', 20)->default('members');
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['published_at', 'country']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
