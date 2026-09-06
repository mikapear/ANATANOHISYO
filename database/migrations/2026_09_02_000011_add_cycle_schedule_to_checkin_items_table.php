<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkin_items', function (Blueprint $table) {
            $table->unsignedSmallInteger('cycle_on_days')->nullable()->after('weekdays');
            $table->unsignedSmallInteger('cycle_rest_days')->nullable()->after('cycle_on_days');
        });
    }

    public function down(): void
    {
        Schema::table('checkin_items', function (Blueprint $table) {
            $table->dropColumn(['cycle_on_days', 'cycle_rest_days']);
        });
    }
};
