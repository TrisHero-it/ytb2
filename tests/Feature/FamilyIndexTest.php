<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/families');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_sees_family_list(): void
    {
        $user = User::factory()->create();
        Family::create([
            'email' => 'owner@example.com',
            'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank',
            'user' => 'Nguyen Van A',
        ]);

        $response = $this->actingAs($user)->get('/families');

        $response->assertOk();
        $response->assertSee('Nguyen Van A');
    }

    public function test_search_query_param_filters_results(): void
    {
        $user = User::factory()->create();
        $family = Family::create([
            'email' => 'owner@example.com',
            'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank',
            'user' => 'Matched Family',
        ]);
        $family->members()->create(['order_code' => 'DH777', 'email' => 'a@example.com']);

        $response = $this->actingAs($user)->get('/families?search=DH777');

        $response->assertOk();
        $response->assertSee('Matched Family');
    }

    public function test_index_page_includes_a_quick_pay_form_for_each_family(): void
    {
        $user = User::factory()->create();
        $family = Family::create([
            'email' => 'owner@example.com',
            'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank',
            'user' => 'Nguyen Van A',
        ]);

        $response = $this->actingAs($user)->get('/families');

        $response->assertOk();
        $response->assertSee(route('families.quick-pay', $family), false);
        $response->assertSee('name="monthly_payment"', false);
    }

    public function test_index_page_shows_days_until_family_empty_column(): void
    {
        $user = User::factory()->create();
        $family = Family::create([
            'email' => 'owner@example.com',
            'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank',
            'user' => 'Nguyen Van A',
        ]);

        // Land on the 15th of a month ~45-75 days out so subtracting 6 months never crosses a short month.
        $target = now()->startOfDay()->addMonths(2)->startOfMonth()->addDays(14);
        $purchaseDate = (clone $target)->subMonths(6);
        $expectedDays = now()->startOfDay()->diffInDays($target, false);

        $family->members()->create([
            'order_code' => 'DH1',
            'email' => 'a@example.com',
            'product_name' => 'YouTube Premium 6 Tháng',
            'purchase_date' => $purchaseDate->toDateString(),
        ]);

        $emptyFamily = Family::create([
            'email' => 'empty@example.com',
            'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank',
            'user' => 'Family Trong',
        ]);

        $response = $this->actingAs($user)->get('/families');

        $response->assertOk();
        $response->assertSee('Ngày family trống');
        $response->assertSee('Còn lại');
        $response->assertSee("{$expectedDays} ngày");
        $response->assertSee('Chưa có thành viên');
    }

    public function test_family_empty_days_column_is_colored_orange_when_near_due_and_red_when_overdue(): void
    {
        $user = User::factory()->create();

        $nearDueFamily = Family::create([
            'email' => 'near-due@example.com',
            'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank',
            'user' => 'Near Due Family',
        ]);
        $nearDueTarget = now()->startOfDay()->addDays(3);
        $nearDueFamily->members()->create([
            'order_code' => 'DH-NEAR',
            'email' => 'near@example.com',
            'product_name' => 'YouTube Premium 6 Tháng',
            'purchase_date' => (clone $nearDueTarget)->subMonths(6)->toDateString(),
        ]);

        $overdueFamily = Family::create([
            'email' => 'overdue@example.com',
            'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank',
            'user' => 'Overdue Family',
        ]);
        $overdueTarget = now()->startOfDay()->subDays(3);
        $overdueFamily->members()->create([
            'order_code' => 'DH-OVERDUE',
            'email' => 'overdue@example.com',
            'product_name' => 'YouTube Premium 6 Tháng',
            'purchase_date' => (clone $overdueTarget)->subMonths(6)->toDateString(),
        ]);

        $response = $this->actingAs($user)->get('/families');

        $response->assertOk();
        $response->assertSee('color: #f59e0b', false);
        $response->assertSee('color: #dc2626', false);
    }

    public function test_index_page_shows_days_until_next_payment_column(): void
    {
        $user = User::factory()->create();
        Family::create([
            'email' => 'owner@example.com',
            'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank',
            'user' => 'Nguyen Van A',
            'auto_payment_day' => 1,
            'monthly_payment' => 100000,
        ]);
        Family::create([
            'email' => 'no-auto-pay@example.com',
            'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank',
            'user' => 'No Auto Pay',
        ]);

        $response = $this->actingAs($user)->get('/families');

        $response->assertOk();
        $response->assertSee('Hạn thanh toán youtube');
        $response->assertSee('Chưa đặt ngày tự thanh toán');
        $this->assertMatchesRegularExpression('/\d+ ngày/', $response->getContent());
    }

    public function test_index_page_includes_a_members_button_and_popup_for_each_family(): void
    {
        $user = User::factory()->create();
        $family = Family::create([
            'email' => 'owner@example.com',
            'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank',
            'user' => 'Nguyen Van A',
        ]);
        $family->members()->create([
            'order_code' => 'DH123',
            'product_name' => 'YouTube Premium 6 Tháng',
            'email' => 'member@example.com',
            'region' => 'Hà Nội',
            'purchase_date' => '2026-03-15',
        ]);

        $response = $this->actingAs($user)->get('/families');

        $response->assertOk();
        $response->assertSee("members-modal-{$family->id}", false);
        $response->assertSee('DH123');
        $response->assertSee('member@example.com');
        $response->assertSee('15/03/2026');
    }

    public function test_index_page_includes_a_history_button_and_modal_for_each_family(): void
    {
        $user = User::factory()->create();
        $family = Family::create([
            'email' => 'owner@example.com',
            'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank',
            'user' => 'Nguyen Van A',
        ]);

        $response = $this->actingAs($user)->get('/families');

        $response->assertOk();
        $response->assertSee("showFamilyHistory({$family->id})", false);
        $response->assertSee("history-modal-{$family->id}", false);
        $response->assertSee("history-list-{$family->id}", false);
    }
}
