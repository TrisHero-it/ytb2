<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Bấm đúp "Xác nhận thanh toán" từng cộng tháng hai lần. Chốt chặn ở trình duyệt
 * chỉ sống trong một trang đang mở, nên server phải tự nhận ra lần bấm trùng.
 */
class QuickPayDuplicateGuardTest extends TestCase
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
            'next_payment_at' => '2026-11-11',
        ], $overrides));
    }

    private function pay(User $user, Family $family, int $months)
    {
        return $this->actingAs($user)->post("/families/{$family->id}/quick-pay", ['months' => $months]);
    }

    public function test_the_second_identical_payment_within_the_window_is_ignored(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();

        $this->pay($user, $family, 1);
        $this->pay($user, $family, 1);

        $this->assertSame('2026-12-11', $family->fresh()->next_payment_at->toDateString());
        $this->assertDatabaseCount('history_joining_family', 1);
    }

    public function test_the_ignored_click_leaves_no_extra_bill_image(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();
        $pastedImage = 'data:image/png;base64,'.base64_encode('fake-png-bytes');

        $this->actingAs($user)->post("/families/{$family->id}/quick-pay", [
            'months' => 1, 'bill_payment_paste' => $pastedImage,
        ]);
        $this->actingAs($user)->post("/families/{$family->id}/quick-pay", [
            'months' => 1, 'bill_payment_paste' => $pastedImage,
        ]);

        $bills = json_decode($family->fresh()->bill_payment, true);

        $this->assertCount(1, $bills);
        $this->assertCount(1, Storage::disk('public')->files('bills'));
    }

    public function test_the_same_payment_is_accepted_again_once_the_window_has_passed(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();

        $this->pay($user, $family, 1);

        $this->travel(11)->seconds();
        $this->pay($user, $family, 1);

        $this->assertSame('2027-01-11', $family->fresh()->next_payment_at->toDateString());
        $this->assertDatabaseCount('history_joining_family', 2);
    }

    public function test_paying_a_different_number_of_months_is_not_treated_as_a_duplicate(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();

        $this->pay($user, $family, 1);
        $this->pay($user, $family, 2);

        $this->assertSame('2027-02-11', $family->fresh()->next_payment_at->toDateString());
        $this->assertDatabaseCount('history_joining_family', 2);
    }

    public function test_another_family_paid_in_the_same_moment_is_not_blocked(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();
        $other = $this->makeFamily(['email' => 'other@example.com', 'user' => 'Tran Thi B']);

        $this->pay($user, $family, 1);
        $this->pay($user, $other, 1);

        $this->assertSame('2026-12-11', $family->fresh()->next_payment_at->toDateString());
        $this->assertSame('2026-12-11', $other->fresh()->next_payment_at->toDateString());
        $this->assertDatabaseCount('history_joining_family', 2);
    }

    /** Chặn theo từng người bấm, để hai nhân viên cùng làm việc không chặn nhau. */
    public function test_a_different_user_is_not_blocked(): void
    {
        $family = $this->makeFamily();

        $this->pay(User::factory()->create(), $family, 1);
        $this->pay(User::factory()->create(), $family, 1);

        $this->assertSame('2027-01-11', $family->fresh()->next_payment_at->toDateString());
        $this->assertDatabaseCount('history_joining_family', 2);
    }

    public function test_the_ignored_click_still_tells_the_user_what_happened(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();

        $this->pay($user, $family, 1);
        $response = $this->pay($user, $family, 1);

        $response->assertRedirect('/families');
        $this->assertStringContainsString('trùng', session('success'));
    }
}
