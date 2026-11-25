<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('title', 160);
            $table->date('start');
            $table->date('end');
            $table->timestamps();
            $table->index(['user_id', 'updated_at']);
        });
        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_thread_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->text('content')->nullable();
            $table->string('status', 20)->default('completed');
            $table->json('evidence')->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->string('model')->nullable();
            $table->timestamps();
            $table->index(['ai_thread_id', 'status']);
        });
        Schema::create('business_insights', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint', 64)->unique();
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('type', 50);
            $table->string('priority', 20);
            $table->string('title');
            $table->text('summary');
            $table->text('recommendation');
            $table->json('evidence');
            $table->date('start');
            $table->date('end');
            $table->string('status', 20)->default('new');
            $table->timestamps();
            $table->index(['branch_id', 'end', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_insights');
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_threads');
    }
};
