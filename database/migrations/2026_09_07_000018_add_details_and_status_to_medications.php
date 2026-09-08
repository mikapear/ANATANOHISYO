<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkin_items', function (Blueprint $table) {
            $table->decimal('dose_amount', 8, 2)->nullable()->after('medication_timings');
            $table->string('dose_unit', 30)->nullable()->after('dose_amount');
            $table->text('medication_instructions')->nullable()->after('dose_unit');
            $table->text('medication_precautions')->nullable()->after('medication_instructions');
        });

        Schema::table('checkin_entries', function (Blueprint $table) {
            $table->string('status', 20)->default('taken')->after('timing');
            $table->timestamp('confirmed_at')->nullable()->after('status');
            $table->index(['user_id', 'status', 'checked_on']);
        });
    }

    public function down(): void
    {
        Schema::table('checkin_entries', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status', 'checked_on']);
            $table->dropColumn(['status', 'confirmed_at']);
        });

        Schema::table('checkin_items', function (Blueprint $table) {
            $table->dropColumn([
                'dose_amount', 'dose_unit', 'medication_instructions', 'medication_precautions',
            ]);
        });
    }
};
