<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('history_joining_family', function (Blueprint $table) {
            $table->dropForeign(['family_id']);
            $table->foreign('family_id')->references('id')->on('families')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('history_joining_family', function (Blueprint $table) {
            $table->dropForeign(['family_id']);
            $table->foreign('family_id')->references('id')->on('families');
        });
    }
};
