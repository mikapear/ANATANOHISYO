<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkin_entries', function (Blueprint $table) {
            $table->index('checkin_item_id', 'checkin_entries_item_fk_index');
        });

        Schema::table('checkin_entries', function (Blueprint $table) {
            $table->dropUnique(['checkin_item_id', 'checked_on']);
            $table->string('timing')->default('once')->after('checked_on');
            $table->unique(['checkin_item_id', 'checked_on', 'timing']);
        });
    }

    public function down(): void
    {
        Schema::table('checkin_entries', function (Blueprint $table) {
            $table->dropUnique(['checkin_item_id', 'checked_on', 'timing']);
            $table->dropColumn('timing');
            $table->unique(['checkin_item_id', 'checked_on']);
        });

        Schema::table('checkin_entries', function (Blueprint $table) {
            $table->dropIndex('checkin_entries_item_fk_index');
        });
    }
};
