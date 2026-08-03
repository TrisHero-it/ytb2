<?php

namespace App\Console\Commands;

use App\Models\Family;
use Illuminate\Console\Command;

class DecrementPassedAutoPaymentMonths extends Command
{
    protected $signature = 'families:decrement-passed-auto-payment-months';

    protected $description = 'Decrement monthly_payment by one for families whose auto_payment_day occurs today, going negative once prepaid credit runs out to mark them overdue.';

    public function handle(): int
    {
        $today = now();

        Family::query()
            ->whereNotNull('auto_payment_day')
            ->get()
            ->each(function (Family $family) use ($today) {
                $dueDayThisMonth = min($family->auto_payment_day, $today->daysInMonth);

                if ($today->day === $dueDayThisMonth) {
                    $family->decrement('monthly_payment');
                }
            });

        return self::SUCCESS;
    }
}
