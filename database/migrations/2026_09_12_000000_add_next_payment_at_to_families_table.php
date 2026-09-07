<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->date('next_payment_at')->nullable()->after('payment_at');
        });

        $this->backfillFromAutoPaymentDay();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->dropColumn('next_payment_at');
        });
    }

    /**
     * Seed the new column with the date the old auto_payment_day + monthly_payment
     * pair used to produce: the next upcoming due day plus the prepaid months.
     */
    private function backfillFromAutoPaymentDay(): void
    {
        $today = Carbon::today();

        DB::table('families')
            ->whereNotNull('auto_payment_day')
            ->orderBy('id')
            ->chunkById(200, function ($families) use ($today) {
                foreach ($families as $family) {
                    $dueDayThisMonth = min((int) $family->auto_payment_day, $today->daysInMonth);
                    $monthsAhead = (int) ($family->monthly_payment ?? 0) + ($dueDayThisMonth >= $today->day ? 0 : 1);

                    $targetMonth = $today->copy()->startOfMonth()->addMonths($monthsAhead);
                    $dueDate = $targetMonth->copy()->addDays(min((int) $family->auto_payment_day, $targetMonth->daysInMonth) - 1);

                    DB::table('families')
                        ->where('id', $family->id)
                        ->update(['next_payment_at' => $dueDate->toDateString()]);
                }
            });
    }
};
