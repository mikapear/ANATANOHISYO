<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('life_goal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('life_goal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('recorded_on');
            $table->string('status', 20);
            $table->decimal('actual_amount', 8, 2)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['life_goal_id', 'recorded_on']);
            $table->index(['user_id', 'recorded_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('life_goal_entries');
    }
};