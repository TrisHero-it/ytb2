<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyQuickPayTest extends TestCase
{
    use RefreshDatabase;

    private function makeFamily(array $overrides = []): Family
    {
        return Family::create(array_merge([
            'email' => 'owner@example.com', 'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank', 'user' => 'Nguyen Van A',
        ], $overrides));
    }

    public function test_quick_pay_marks_payment_at_as_today(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();

        $response = $this->actingAs($user)->post("/families/{$family->id}/quick-pay", []);

        $response->assertRedirect('/families');
        $family->refresh();
        $this->assertSame(now()->toDateString(), $family->payment_at->toDateString());
    }

    public function test_quick_pay_adds_to_existing_monthly_payment_when_provided(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily(['monthly_payment' => 1]);

        $this->actingAs($user)->post("/families/{$family->id}/quick-pay", [
            'monthly_payment' => 3,
        ]);

        $family->refresh();
        $this->assertSame(4, $family->monthly_payment);
    }

    public function test_quick_pay_keeps_existing_monthly_payment_when_not_provided(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily(['monthly_payment' => 100000]);

        $this->actingAs($user)->post("/families/{$family->id}/quick-pay", []);

        $family->refresh();
        $this->assertSame(100000, $family->monthly_payment);
    }

    public function test_quick_pay_rejects_negative_monthly_payment(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();

        $response = $this->actingAs($user)->post("/families/{$family->id}/quick-pay", [
            'monthly_payment' => -1,
        ]);

        $response->assertSessionHasErrors('monthly_payment');
    }
}
