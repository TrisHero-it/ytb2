<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FamilyQuickPayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

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

    public function test_quick_pay_stores_pasted_bill_image(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();
        $pixelPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        $dataUrl = 'data:image/png;base64,'.base64_encode($pixelPng);

        $response = $this->actingAs($user)->post("/families/{$family->id}/quick-pay", [
            'bill_payment_paste' => $dataUrl,
        ]);

        $response->assertRedirect('/families');
        $family->refresh();
        $this->assertNotNull($family->bill_payment);
        $stored = json_decode($family->bill_payment, true);
        $this->assertCount(1, $stored);
        Storage::disk('public')->assertExists('bills/'.$stored[0]);
    }
}
