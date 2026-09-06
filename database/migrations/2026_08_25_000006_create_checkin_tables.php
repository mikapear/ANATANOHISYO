<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkin_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['project_id', 'is_active', 'position']);
        });

        Schema::create('checkin_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checkin_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('checked_on');
            $table->string('note', 500)->nullable();
            $table->timestamps();
            $table->unique(['checkin_item_id', 'checked_on']);
            $table->index(['user_id', 'checked_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkin_entries');
        Schema::dropIfExists('checkin_items');
    }
};
