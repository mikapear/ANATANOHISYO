<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkin_items', function (Blueprint $table) {
            $table->string('schedule_type')->default('daily')->after('medication_timings');
            $table->json('weekdays')->nullable()->after('schedule_type');
            $table->date('starts_on')->nullable()->after('weekdays');
            $table->date('ends_on')->nullable()->after('starts_on');
        });
    }

    public function down(): void
    {
        Schema::table('checkin_items', function (Blueprint $table) {
            $table->dropColumn(['schedule_type', 'weekdays', 'starts_on', 'ends_on']);
        });
    }
};
