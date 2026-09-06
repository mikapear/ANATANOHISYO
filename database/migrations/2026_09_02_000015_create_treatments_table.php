<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('treatment_type', 30);
            $table->date('scheduled_on');
            $table->time('scheduled_at')->nullable();
            $table->unsignedSmallInteger('cycle_number')->nullable();
            $table->string('hospital')->nullable();
            $table->string('department')->nullable();
            $table->text('note')->nullable();
            $table->string('status', 20)->default('scheduled');
            $table->timestamps();

            $table->index(['user_id', 'scheduled_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatments');
    }
};