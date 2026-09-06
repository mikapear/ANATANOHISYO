<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkin_items', function (Blueprint $table) {
            $table->string('kind')->default('checkin')->after('title');
            $table->json('medication_timings')->nullable()->after('kind');
            $table->index(['project_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::table('checkin_items', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'kind']);
            $table->dropColumn(['kind', 'medication_timings']);
        });
    }
};
