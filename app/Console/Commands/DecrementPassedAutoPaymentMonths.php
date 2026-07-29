<?php

namespace App\Console\Commands;

use App\Models\Family;
use Illuminate\Console\Command;

class DecrementPassedAutoPaymentMonths extends Command
{
    protected $signature = 'families:decrement-passed-auto-payment-months';

    protected $description = 'Consume one month of prepaid credit (monthly_payment) for families whose auto_payment_day occurs today.';

    public function handle(): int
    {
        $today = now();

        Family::query()
            ->whereNotNull('auto_payment_day')
            ->where('monthly_payment', '>', 0)
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
