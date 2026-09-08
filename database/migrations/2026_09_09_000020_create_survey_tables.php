<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('version')->default(1);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('survey_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_definition_id')->constrained()->cascadeOnDelete();
            $table->string('key', 80);
            $table->text('prompt');
            $table->string('response_type', 30);
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['survey_definition_id', 'key']);
            $table->index(['survey_definition_id', 'position']);
        });

        Schema::create('survey_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('survey_definition_id')->constrained()->cascadeOnDelete();
            $table->string('phase', 20);
            $table->date('due_on');
            $table->date('available_from');
            $table->date('available_until')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'survey_definition_id', 'phase']);
            $table->index(['user_id', 'status', 'due_on']);
        });

        Schema::create('survey_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('survey_question_id')->constrained()->cascadeOnDelete();
            $table->json('response');
            $table->timestamp('answered_at');
            $table->timestamps();
            $table->unique(['survey_assignment_id', 'survey_question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_answers');
        Schema::dropIfExists('survey_assignments');
        Schema::dropIfExists('survey_questions');
        Schema::dropIfExists('survey_definitions');
    }
};
