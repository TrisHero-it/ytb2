<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoubleSubmitGuardTest extends TestCase
{
    use RefreshDatabase;

    private function makeFamily(): Family
    {
        return Family::create([
            'email' => 'owner@example.com', 'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank', 'user' => 'Nguyen Van A',
            'next_payment_at' => '2026-11-11',
        ]);
    }

    public function test_pages_ship_the_guard_that_blocks_a_second_submit(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/families');

        $response->assertOk();
        $response->assertSee("form.dataset.submitting === '1'", false);
        $response->assertSee('button.disabled = true', false);
    }

    public function test_the_loading_overlay_markup_has_styles_so_it_is_actually_visible(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/families');

        $response->assertOk();
        $response->assertSee('id="page_loading_overlay"', false);
        $response->assertSee('.page-loading-overlay {', false);
        $response->assertSee('.page-loading-overlay.hidden {', false);
    }

    public function test_the_quick_pay_form_announces_what_it_is_doing(): void
    {
        $user = User::factory()->create();
        $this->makeFamily();

        $response = $this->actingAs($user)->get('/families');

        $response->assertOk();
        $response->assertSee('data-loading-text="Đang ghi nhận thanh toán..."', false);
    }

    public function test_the_history_search_form_opts_out_of_the_overlay(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/families');

        $response->assertOk();
        $response->assertSee('onsubmit="searchHistory(event)" data-no-loading', false);
    }

    public function test_one_quick_pay_of_one_month_moves_the_due_date_by_exactly_one_month(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();

        $this->actingAs($user)->post("/families/{$family->id}/quick-pay", ['months' => 1]);

        $this->assertSame('2026-12-11', $family->fresh()->next_payment_at->toDateString());
        $this->assertDatabaseCount('history_joining_family', 1);
    }

    /**
     * Nếu chốt chặn ở trình duyệt hỏng, hai request vẫn cộng hai lần. Test này
     * ghi lại đúng hành vi đó để sau này ai đọc cũng biết vì sao cần chặn.
     */
    public function test_two_quick_pay_requests_do_move_the_due_date_twice(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();

        $this->actingAs($user)->post("/families/{$family->id}/quick-pay", ['months' => 1]);
        $this->actingAs($user)->post("/families/{$family->id}/quick-pay", ['months' => 1]);

        $this->assertSame('2027-01-11', $family->fresh()->next_payment_at->toDateString());
        $this->assertDatabaseCount('history_joining_family', 2);
    }
}
