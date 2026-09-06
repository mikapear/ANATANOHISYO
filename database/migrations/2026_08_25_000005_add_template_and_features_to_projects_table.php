<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('template')->default('planning')->after('status');
            $table->boolean('uses_todos')->default(true)->after('template');
            $table->boolean('uses_checkins')->default(false)->after('uses_todos');
            $table->boolean('uses_activity_logs')->default(true)->after('uses_checkins');
            $table->boolean('uses_calendar')->default(true)->after('uses_activity_logs');
            $table->index(['user_id', 'template']);
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'template']);
            $table->dropColumn(['template', 'uses_todos', 'uses_checkins', 'uses_activity_logs', 'uses_calendar']);
        });
    }
};
