<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value');
            $table->timestamps();
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('event_key');
            $table->string('title');
            $table->text('message');
            $table->string('link');
            $table->timestamp('read_at')->nullable();
            $table->unique(['user_id', 'event_key']);
            $table->timestamps();
        });
        Schema::table('expenses', function (Blueprint $table) {
            $table->index(['branch_id', 'expense_date']);
        });
        Schema::table('payrolls', function (Blueprint $table) {
            $table->index(['branch_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('settings');
        Schema::table(
            'expenses',
            fn(Blueprint $table) => $table->dropIndex(['branch_id', 'expense_date']),
        );
        Schema::table(
            'payrolls',
            fn(Blueprint $table) => $table->dropIndex(['branch_id', 'period']),
        );
    }
};
