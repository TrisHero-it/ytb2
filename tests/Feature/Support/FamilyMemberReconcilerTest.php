<?php

namespace Tests\Feature\Support;

use App\Models\Family;
use App\Support\FamilyMemberReconciler;
use App\Support\MemberTextParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyMemberReconcilerTest extends TestCase
{
    use RefreshDatabase;

    private function makeFamily(): Family
    {
        return Family::create([
            'form' => 'ck',
            'email' => 'owner@example.com',
            'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank',
            'user' => 'Nguyen Van A',
            'status' => 'cho thanh toan',
            'pay_due_date' => '2026-08-01',
        ]);
    }

    public function test_row_without_id_is_added(): void
    {
        $family = $this->makeFamily();

        $diffs = FamilyMemberReconciler::reconcile($family, [
            ['id' => null, 'text' => "Mã đơn hàng: DH1\nTên sản phẩm: YouTube\nEmail: a@example.com\nKhu vực bạn sống: HN\nNgày mua: 01/01/2026"],
        ]);

        $this->assertCount(1, $diffs);
        $this->assertSame('add', $diffs[0]['action']);
        $this->assertDatabaseHas('family_members', ['family_id' => $family->id, 'email' => 'a@example.com']);
    }

    public function test_row_with_changed_fields_is_updated(): void
    {
        $family = $this->makeFamily();
        $member = $family->members()->create(['order_code' => 'DH1', 'email' => 'old@example.com']);

        $diffs = FamilyMemberReconciler::reconcile($family, [
            ['id' => $member->id, 'text' => "Mã đơn hàng: DH1\nTên sản phẩm: YouTube\nEmail: new@example.com\nKhu vực bạn sống: HN\nNgày mua: 01/01/2026"],
        ]);

        $this->assertCount(1, $diffs);
        $this->assertSame('change', $diffs[0]['action']);
        $this->assertSame('old@example.com', $diffs[0]['old']['email']);
        $this->assertDatabaseHas('family_members', ['id' => $member->id, 'email' => 'new@example.com']);
    }

    public function test_row_with_unchanged_fields_produces_no_diff(): void
    {
        $family = $this->makeFamily();
        $text = json_encode(['order_code' => 'DH1', 'email' => 'same@example.com']);
        $member = $family->members()->create(MemberTextParser::parse($text));

        $diffs = FamilyMemberReconciler::reconcile($family, [
            ['id' => $member->id, 'text' => $text],
        ]);

        $this->assertCount(0, $diffs);
    }

    public function test_existing_member_missing_from_submission_is_deleted(): void
    {
        $family = $this->makeFamily();
        $member = $family->members()->create(['order_code' => 'DH1', 'email' => 'gone@example.com']);

        $diffs = FamilyMemberReconciler::reconcile($family, []);

        $this->assertCount(1, $diffs);
        $this->assertSame('delete', $diffs[0]['action']);
        $this->assertDatabaseMissing('family_members', ['id' => $member->id]);
    }
}
