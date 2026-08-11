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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('todo_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->date('performed_on');
            $table->time('performed_at')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->text('memo')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'performed_on']);
            $table->index(['user_id', 'project_id']);
            $table->index('todo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
