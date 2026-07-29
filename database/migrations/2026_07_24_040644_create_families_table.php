<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->string('form');
            $table->date('payment_at')->nullable();
            $table->string('email');
            $table->string('number_phone', 20)->nullable();
            $table->string('number_bank', 100);
            $table->string('name_bank', 100);
            $table->string('user');
            $table->integer('monthly_payment')->default(1);
            $table->integer('month_master_pay')->default(1);
            $table->integer('month_to_pay')->nullable();
            $table->string('afiilicate_by')->nullable();
            $table->text('bill_of_master')->nullable();
            $table->text('bill_payment')->nullable();
            $table->string('status');
            $table->date('pay_due_date');
            $table->text('note')->nullable();
        });
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Schema::dropIfExists('families');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
