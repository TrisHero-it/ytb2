<?php

namespace Tests\Feature\Console;

use App\Models\Family;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DecrementPassedAutoPaymentMonthsTest extends TestCase
{
    use RefreshDatabase;

    private function makeFamily(array $overrides = []): Family
    {
        return Family::create(array_merge([
            'email' => 'owner@example.com',
            'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank',
            'user' => 'Nguyen Van A',
        ], $overrides));
    }

    public function test_decrements_monthly_payment_when_today_matches_auto_payment_day(): void
    {
        $this->travelTo(now()->startOfMonth()->addDays(9));
        $family = $this->makeFamily(['auto_payment_day' => 10, 'monthly_payment' => 4]);

        $this->artisan('families:decrement-passed-auto-payment-months')->assertSuccessful();

        $this->assertSame(3, $family->refresh()->monthly_payment);
    }

    public function test_does_not_decrement_when_today_does_not_match_auto_payment_day(): void
    {
        $this->travelTo(now()->startOfMonth()->addDays(4));
        $family = $this->makeFamily(['auto_payment_day' => 10, 'monthly_payment' => 4]);

        $this->artisan('families:decrement-passed-auto-payment-months')->assertSuccessful();

        $this->assertSame(4, $family->refresh()->monthly_payment);
    }

    public function test_does_not_decrement_below_zero(): void
    {
        $this->travelTo(now()->startOfMonth()->addDays(9));
        $family = $this->makeFamily(['auto_payment_day' => 10, 'monthly_payment' => 0]);

        $this->artisan('families:decrement-passed-auto-payment-months')->assertSuccessful();

        $this->assertSame(0, $family->refresh()->monthly_payment);
    }

    public function test_ignores_families_without_auto_payment_day(): void
    {
        $this->travelTo(now()->startOfMonth()->addDays(9));
        $family = $this->makeFamily(['monthly_payment' => 4]);

        $this->artisan('families:decrement-passed-auto-payment-months')->assertSuccessful();

        $this->assertSame(4, $family->refresh()->monthly_payment);
    }

    public function test_clamps_auto_payment_day_to_last_day_of_short_month(): void
    {
        $this->travelTo(now()->setDate(2027, 2, 28)->startOfDay());
        $family = $this->makeFamily(['auto_payment_day' => 31, 'monthly_payment' => 2]);

        $this->artisan('families:decrement-passed-auto-payment-months')->assertSuccessful();

        $this->assertSame(1, $family->refresh()->monthly_payment);
    }
}
