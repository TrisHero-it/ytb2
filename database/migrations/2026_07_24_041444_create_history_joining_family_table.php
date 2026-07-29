<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('history_joining_family', function (Blueprint $table) {
            $table->id();
            $table->string('order_id');
            $table->foreignId('family_id')->constrained('families');
            $table->string('status')->default('add');
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->string('name_product');
            $table->string('email');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Schema::dropIfExists('history_joining_family');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
