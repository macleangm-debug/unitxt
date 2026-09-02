<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_documents', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64);
            $table->string('version', 16);
            $table->string('audience', 32)->default('all');
            $table->string('status', 16)->default('draft');
            $table->boolean('requires_acceptance')->default(false);
            $table->boolean('counsel_reviewed')->default(false);
            $table->date('effective_on')->nullable();
            $table->date('retired_on')->nullable();
            $table->string('title_en');
            $table->string('title_sw');
            $table->string('summary_en', 500)->nullable();
            $table->string('summary_sw', 500)->nullable();
            $table->longText('body_en');
            $table->longText('body_sw');
            $table->timestamps();

            $table->unique(['slug', 'version']);
            $table->index(['slug', 'status']);
        });

        Schema::create('legal_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('legal_document_id')->constrained()->cascadeOnDelete();
            $table->string('role', 24)->nullable();
            $table->string('method', 32);
            $table->timestamp('accepted_at');
            $table->timestamps();

            $table->index(['user_id', 'legal_document_id']);
        });

        Schema::create('privacy_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 32);
            $table->string('status', 24)->default('open');
            $table->text('detail')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('marketing_opt_in')->default(false)->after('profile_completed');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->index(['country', 'is_active', 'name']);
            $table->index(['country', 'sector']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('marketing_opt_in');
        });
        Schema::dropIfExists('privacy_requests');
        Schema::dropIfExists('legal_acceptances');
        Schema::dropIfExists('legal_documents');
    }
};
