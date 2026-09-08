<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('research_code', 24)->nullable()->unique()->after('is_admin');
        });

        DB::table('users')->whereNull('research_code')->orderBy('id')->eachById(function ($user) {
            DB::table('users')->where('id', $user->id)->update([
                'research_code' => Str::upper(Str::random(16)),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('research_code');
        });
    }
};
