<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyEditTest extends TestCase
{
    use RefreshDatabase;

    private function makeFamily(): Family
    {
        return Family::create([
            'email' => 'owner@example.com', 'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank', 'user' => 'Nguyen Van A',
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $family = $this->makeFamily();

        $response = $this->get("/families/{$family->id}/edit");

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_sees_prefilled_edit_form(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();
        $family->members()->create(['order_code' => 'DH1', 'email' => 'member@example.com']);

        $response = $this->actingAs($user)->get("/families/{$family->id}/edit");

        $response->assertOk();
        $response->assertSee('Nguyen Van A');
        $response->assertSee('DH1');
    }

    public function test_edit_form_includes_payment_at_field_prefilled(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();
        $family->payment_at = '2026-04-10';
        $family->save();

        $response = $this->actingAs($user)->get("/families/{$family->id}/edit");

        $response->assertOk();
        $response->assertSee('name="payment_at"', false);
        $response->assertSee('2026-04-10', false);
    }

    public function test_edit_form_wires_member_email_duplicate_check_with_exclude_id(): void
    {
        $user = User::factory()->create();
        $family = Family::create([
            'form' => 'ck', 'email' => 'owner@example.com', 'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank', 'user' => 'Nguyen Van A', 'status' => 'cho thanh toan',
            'pay_due_date' => '2026-08-01',
        ]);

        $response = $this->actingAs($user)->get("/families/{$family->id}/edit");

        $response->assertOk();
        $response->assertSee("checkMemberEmailDuplicate(this, {$family->id})", false);
    }
}
