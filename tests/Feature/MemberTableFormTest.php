<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\User;
use App\Support\MemberFormRows;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberTableFormTest extends TestCase
{
    use RefreshDatabase;

    private const PASTED_TEXT = "Mã đơn hàng: DH202610\nTên sản phẩm: YouTube Premium 12 Tháng\nEmail: Thanhvien@example.com\nKhu vực bạn sống: Đà Nẵng\nNgày mua: 02/10/2026";

    private function makeFamily(): Family
    {
        return Family::create([
            'email' => 'owner@example.com', 'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank', 'user' => 'Nguyen Van A',
        ]);
    }

    public function test_edit_form_shows_members_as_a_table_with_parsed_columns(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();
        $family->members()->create([
            'order_code' => 'DH1',
            'product_name' => 'YouTube Premium 12 Tháng',
            'email' => 'member@example.com',
            'region' => 'Hà Nội',
            'purchase_date' => '2026-03-15',
        ]);

        $response = $this->actingAs($user)->get("/families/{$family->id}/edit");

        $response->assertOk();

        foreach (['Mã đơn hàng', 'Tên sản phẩm', 'Email', 'Khu vực bạn sống', 'Ngày mua'] as $heading) {
            $response->assertSee($heading);
        }

        $response->assertSee('DH1');
        $response->assertSee('YouTube Premium 12 Tháng');
        $response->assertSee('member@example.com');
        $response->assertSee('Hà Nội');
        $response->assertSee('15/03/2026');
    }

    public function test_the_pasted_text_is_kept_in_a_hidden_input_not_a_textarea(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();
        $family->members()->create(['order_code' => 'DH1', 'email' => 'member@example.com', 'raw_text' => self::PASTED_TEXT]);

        $response = $this->actingAs($user)->get("/families/{$family->id}/edit");

        $response->assertOk();
        $response->assertSee('name="member_texts[]"', false);
        $response->assertDontSee('<textarea name="member_texts[]"', false);
        $response->assertSee('DH202610', false);
    }

    public function test_both_forms_offer_a_paste_box_for_adding_members(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();

        foreach (['/families/create', "/families/{$family->id}/edit"] as $url) {
            $response = $this->actingAs($user)->get($url);

            $response->assertOk();
            $response->assertSee('id="member-editor-text"', false);
            $response->assertSee('Dán nội dung đơn hàng', false);
            $response->assertSee('data-member-email-warning', false);
        }
    }

    public function test_edit_form_keeps_what_was_typed_when_validation_fails(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();

        // Hai thành viên trùng email thì UpdateFamilyRequest sẽ chặn.
        $response = $this->actingAs($user)
            ->from("/families/{$family->id}/edit")
            ->put("/families/{$family->id}", [
                'email' => $family->email,
                'user' => $family->user,
                'number_bank' => $family->number_bank,
                'name_bank' => $family->name_bank,
                'member_texts' => [self::PASTED_TEXT, self::PASTED_TEXT],
                'member_ids' => ['', ''],
            ]);

        $response->assertSessionHasErrors();

        $followUp = $this->actingAs($user)->get("/families/{$family->id}/edit");

        $followUp->assertOk();
        $followUp->assertSee('DH202610', false);
    }

    public function test_rows_are_built_from_old_input_when_present(): void
    {
        $family = $this->makeFamily();
        $family->members()->create(['order_code' => 'FROM_DB', 'email' => 'db@example.com']);

        $this->session(['_old_input' => [
            'member_texts' => [self::PASTED_TEXT],
            'member_ids' => [''],
        ]]);
        app('request')->setLaravelSession(app('session.store'));

        $rows = MemberFormRows::forForm($family);

        $this->assertCount(1, $rows);
        $this->assertSame('DH202610', $rows[0]['order_code']);
        $this->assertSame('thanhvien@example.com', $rows[0]['email']);
        $this->assertSame('Đà Nẵng', $rows[0]['region']);
        $this->assertSame('02/10/2026', $rows[0]['purchase_date']);
        $this->assertNull($rows[0]['id']);
        $this->assertSame(self::PASTED_TEXT, $rows[0]['text']);
    }

    public function test_rows_fall_back_to_the_database_when_there_is_no_old_input(): void
    {
        $family = $this->makeFamily();
        $member = $family->members()->create([
            'order_code' => 'FROM_DB',
            'email' => 'db@example.com',
            'purchase_date' => '2026-03-15',
            'raw_text' => self::PASTED_TEXT,
        ]);

        $rows = MemberFormRows::forForm($family->load('members'));

        $this->assertCount(1, $rows);
        $this->assertSame($member->id, $rows[0]['id']);
        $this->assertSame('FROM_DB', $rows[0]['order_code']);
        $this->assertSame('15/03/2026', $rows[0]['purchase_date']);
        $this->assertSame(self::PASTED_TEXT, $rows[0]['text']);
    }

    public function test_a_member_without_raw_text_is_still_editable_as_json(): void
    {
        $family = $this->makeFamily();
        $family->members()->create([
            'order_code' => 'NO_RAW',
            'email' => 'noraw@example.com',
            'purchase_date' => '2026-03-15',
        ]);

        $rows = MemberFormRows::forForm($family->load('members'));

        $decoded = json_decode($rows[0]['text'], true);

        $this->assertIsArray($decoded);
        $this->assertSame('NO_RAW', $decoded['order_code']);
        $this->assertSame('15/03/2026', $decoded['purchase_date']);
    }
}
