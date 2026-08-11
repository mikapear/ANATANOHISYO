<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('todos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recurrence_parent_id')->nullable();
            $table->string('title');
            $table->text('memo')->nullable();
            $table->date('due_date')->nullable();
            $table->time('due_time')->nullable();
            $table->string('priority')->default('medium');
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->string('recurrence')->default('none');
            $table->dateTime('remind_at')->nullable();
            $table->timestamps();

            $table->foreign('recurrence_parent_id')
                ->references('id')
                ->on('todos')
                ->nullOnDelete();

            $table->unique('recurrence_parent_id');
            $table->index(['user_id', 'is_completed', 'due_date']);
            $table->index(['user_id', 'project_id']);
            $table->index(['user_id', 'completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('todos');
    }
};
