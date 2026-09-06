<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('life_goal_entries', function (Blueprint $table) {
            $table->string('nutrition_type', 20)->nullable()->after('actual_amount');
            $table->text('food_details')->nullable()->after('nutrition_type');
        });
    }

    public function down(): void
    {
        Schema::table('life_goal_entries', function (Blueprint $table) {
            $table->dropColumn(['nutrition_type', 'food_details']);
        });
    }
};