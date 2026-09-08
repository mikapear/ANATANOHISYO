<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('event_name', 80);
            $table->nullableMorphs('subject');
            $table->json('context')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->index(['user_id', 'occurred_at']);
            $table->index(['user_id', 'event_name', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_events');
    }
};
