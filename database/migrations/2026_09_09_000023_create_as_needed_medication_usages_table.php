<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkin_items', function (Blueprint $table) {
            $table->boolean('is_as_needed')->default(false)->after('kind');
        });

        Schema::create('as_needed_medication_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checkin_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('used_at');
            $table->string('note', 500)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_needed_medication_usages');
        Schema::table('checkin_items', fn (Blueprint $table) => $table->dropColumn('is_as_needed'));
    }
};
