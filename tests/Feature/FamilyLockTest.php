<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\User;
use App\Support\MemberFormRows;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyLockTest extends TestCase
{
    use RefreshDatabase;

    private function makeFamily(bool $locked = false): Family
    {
        $family = Family::create([
            'email' => 'owner@example.com', 'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank', 'user' => 'Nguyen Van A',
        ]);
        $family->is_locked = $locked;
        $family->save();

        return $family;
    }

    private function validPayload(Family $family, array $overrides = []): array
    {
        return array_merge([
            'email' => $family->email,
            'user' => $family->user,
            'number_bank' => $family->number_bank,
            'name_bank' => $family->name_bank,
        ], $overrides);
    }

    /** Lấy đúng những gì trình duyệt gửi lại cho các dòng thành viên không bị chạm vào. */
    private function untouchedMemberPayload(Family $family): array
    {
        $texts = [];
        $ids = [];

        foreach (MemberFormRows::forForm($family->load('members')) as $row) {
            $rendered = view('families.partials.member-row', ['row' => $row])->render();
            preg_match('/name="member_texts\[\]"[^>]*value="(.*?)"/s', $rendered, $matches);
            $texts[] = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
            $ids[] = $row['id'];
        }

        return ['member_texts' => $texts, 'member_ids' => $ids];
    }

    public function test_guest_cannot_lock_a_family(): void
    {
        $family = $this->makeFamily();

        $this->post("/families/{$family->id}/lock")->assertRedirect('/login');

        $this->assertFalse($family->fresh()->is_locked);
    }

    public function test_locking_and_unlocking_flip_the_flag_and_log_history(): void
    {
        $user = User::factory()->create(['name' => 'Admin']);
        $family = $this->makeFamily();

        $this->actingAs($user)->post("/families/{$family->id}/lock")->assertSessionHas('success');
        $this->assertTrue($family->fresh()->is_locked);

        $this->actingAs($user)->post("/families/{$family->id}/unlock")->assertSessionHas('success');
        $this->assertFalse($family->fresh()->is_locked);

        $this->assertDatabaseHas('history_joining_family', ['family_id' => $family->id, 'status' => 'lock', 'user_name' => 'Admin']);
        $this->assertDatabaseHas('history_joining_family', ['family_id' => $family->id, 'status' => 'unlock', 'user_name' => 'Admin']);
    }

    /** Bấm đúp hoặc gửi lại từ trang cũ không được ghi thêm dòng lịch sử. */
    public function test_locking_an_already_locked_family_logs_nothing(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily(locked: true);

        $this->actingAs($user)->post("/families/{$family->id}/lock");

        $this->assertTrue($family->fresh()->is_locked);
        $this->assertDatabaseMissing('history_joining_family', ['family_id' => $family->id]);
    }

    public function test_update_form_cannot_unlock_a_family(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily(locked: true);

        $this->actingAs($user)->put("/families/{$family->id}", $this->validPayload($family, ['is_locked' => '0']));

        $this->assertTrue($family->fresh()->is_locked);
    }

    public function test_locked_family_rejects_adding_a_member(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily(locked: true);

        $response = $this->actingAs($user)->put("/families/{$family->id}", $this->validPayload($family, [
            'note' => 'Ghi chú mới',
            'member_texts' => [json_encode(['order_code' => 'NEW1', 'email' => 'new@example.com'])],
            'member_ids' => [null],
        ]));

        $response->assertSessionHasErrors('member_texts');
        $this->assertSame(0, $family->members()->count());
        // Bị chặn thì không lưu nửa vời phần thông tin family.
        $this->assertNull($family->fresh()->note);
    }

    public function test_locked_family_rejects_editing_a_member(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily(locked: true);
        $member = $family->members()->create(['order_code' => 'OLD', 'email' => 'old@example.com']);

        $response = $this->actingAs($user)->put("/families/{$family->id}", $this->validPayload($family, [
            'member_texts' => [json_encode(['order_code' => 'OLD', 'email' => 'updated@example.com'])],
            'member_ids' => [$member->id],
        ]));

        $response->assertSessionHasErrors('member_texts');
        $this->assertDatabaseHas('family_members', ['id' => $member->id, 'email' => 'old@example.com']);
    }

    public function test_locked_family_rejects_removing_a_member(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily(locked: true);
        $member = $family->members()->create(['order_code' => 'KEEP', 'email' => 'keep@example.com']);

        $response = $this->actingAs($user)->put("/families/{$family->id}", $this->validPayload($family, [
            'member_texts' => [],
            'member_ids' => [],
        ]));

        $response->assertSessionHasErrors('member_texts');
        $this->assertDatabaseHas('family_members', ['id' => $member->id]);
    }

    /**
     * Khóa chỉ chặn thành viên. Thành viên cũ chưa có `raw_text` được form gửi
     * lại dạng JSON, việc đó không được tính là sửa thành viên.
     */
    public function test_locked_family_still_saves_family_fields_when_members_are_untouched(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily(locked: true);
        $legacy = $family->members()->create([
            'order_code' => 'LEGACY', 'email' => 'legacy@example.com', 'purchase_date' => '2026-03-15',
        ]);
        $pasted = $family->members()->create([
            'order_code' => 'PASTED', 'product_name' => 'YouTube', 'email' => 'pasted@example.com',
            'purchase_date' => '2026-03-15',
            'raw_text' => 'Mã đơn hàng: PASTED Tên sản phẩm: YouTube Email: pasted@example.com Ngày mua: 15/03/2026',
        ]);

        $response = $this->actingAs($user)->put(
            "/families/{$family->id}",
            $this->validPayload($family, ['note' => 'Ghi chú mới']) + $this->untouchedMemberPayload($family),
        );

        $response->assertSessionHasNoErrors();
        $this->assertSame('Ghi chú mới', $family->fresh()->note);
        $this->assertDatabaseHas('family_members', ['id' => $legacy->id, 'raw_text' => null]);
        $this->assertDatabaseHas('family_members', ['id' => $pasted->id]);
        $this->assertDatabaseMissing('history_joining_family', ['family_id' => $family->id, 'status' => 'change']);
    }

    public function test_unlocked_family_can_edit_members_again(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily(locked: true);

        $this->actingAs($user)->post("/families/{$family->id}/unlock");
        $this->actingAs($user)->put("/families/{$family->id}", $this->validPayload($family, [
            'member_texts' => [json_encode(['order_code' => 'NEW1', 'email' => 'new@example.com'])],
            'member_ids' => [null],
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('family_members', ['family_id' => $family->id, 'order_code' => 'NEW1']);
    }

    public function test_edit_page_hides_member_actions_when_locked(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily(locked: true);
        $family->members()->create(['order_code' => 'KEEP', 'email' => 'keep@example.com']);

        $response = $this->actingAs($user)->get("/families/{$family->id}/edit");

        $response->assertOk();
        $response->assertSee(route('families.unlock', $family), false);
        $response->assertDontSee('onclick="openMemberEditor(null)"', false);
        $response->assertDontSee('onclick="removeMemberRow(this)"', false);
        // Vẫn giữ input ẩn, nếu không thì form sẽ gửi lên danh sách rỗng.
        $response->assertSee('name="member_ids[]"', false);
    }

    public function test_edit_page_shows_member_actions_when_unlocked(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();
        $family->members()->create(['order_code' => 'KEEP', 'email' => 'keep@example.com']);

        $response = $this->actingAs($user)->get("/families/{$family->id}/edit");

        $response->assertSee(route('families.lock', $family), false);
        $response->assertSee('onclick="openMemberEditor(null)"', false);
        $response->assertSee('onclick="removeMemberRow(this)"', false);
    }
}
