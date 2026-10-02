<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('history_joining_family', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('family_id')->constrained('users')->nullOnDelete();
            $table->string('user_name')->nullable()->after('user_id');
        });

        Schema::table('history_joining_family', function (Blueprint $table) {
            $table->string('order_id')->nullable()->change();
            $table->string('name_product')->nullable()->change();
            $table->string('email')->nullable()->change();
            $table->text('old_value')->nullable()->change();
            $table->text('new_value')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('history_joining_family', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'user_name']);
        });
    }
};
