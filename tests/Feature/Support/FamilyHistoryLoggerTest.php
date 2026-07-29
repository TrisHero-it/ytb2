<?php

namespace Tests\Feature\Support;

use App\Models\Family;
use App\Models\History;
use App\Support\FamilyHistoryLogger;
use App\Support\FamilyMemberReconciler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyHistoryLoggerTest extends TestCase
{
    use RefreshDatabase;

    private function makeFamily(): Family
    {
        return Family::create([
            'form' => 'ck', 'email' => 'owner@example.com', 'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank', 'user' => 'Nguyen Van A', 'status' => 'cho thanh toan',
            'pay_due_date' => '2026-08-01',
        ]);
    }

    public function test_logs_an_add_diff(): void
    {
        $family = $this->makeFamily();
        $diffs = FamilyMemberReconciler::reconcile($family, [
            ['id' => null, 'text' => "Mã đơn hàng: DH1\nTên sản phẩm: YouTube\nEmail: a@example.com\nKhu vực bạn sống: HN\nNgày mua: 01/01/2026"],
        ]);

        FamilyHistoryLogger::logDiffs($family->id, $diffs);

        $this->assertDatabaseHas('history_joining_family', [
            'family_id' => $family->id,
            'order_id' => 'DH1',
            'status' => 'add',
            'email' => 'a@example.com',
        ]);
        $history = History::where('family_id', $family->id)->first();
        $this->assertNull($history->old_value);
        $new = json_decode($history->new_value, true);
        $this->assertSame('DH1', $new['order_code']);
        $this->assertSame('a@example.com', $new['email']);
        $this->assertArrayNotHasKey('raw_text', $new);
    }

    public function test_logs_a_delete_diff(): void
    {
        $family = $this->makeFamily();
        $family->members()->create(['order_code' => 'DH2', 'email' => 'gone@example.com', 'product_name' => 'YouTube', 'region' => 'HN']);

        $diffs = FamilyMemberReconciler::reconcile($family, []);
        FamilyHistoryLogger::logDiffs($family->id, $diffs);

        $this->assertDatabaseHas('history_joining_family', [
            'family_id' => $family->id,
            'order_id' => 'DH2',
            'status' => 'delete',
            'email' => 'gone@example.com',
        ]);

        $history = History::where('family_id', $family->id)->first();
        $this->assertNull($history->new_value);
        $old = json_decode($history->old_value, true);
        $this->assertSame('DH2', $old['order_code']);
        $this->assertSame('HN', $old['region']);
        $this->assertArrayNotHasKey('raw_text', $old);
    }

    public function test_logs_a_change_diff_with_old_and_new_value_json_excluding_raw_text(): void
    {
        $family = $this->makeFamily();
        $member = $family->members()->create(['order_code' => 'DH3', 'email' => 'old@example.com', 'product_name' => 'YouTube', 'purchase_date' => '2026-01-01']);

        $diffs = FamilyMemberReconciler::reconcile($family, [
            ['id' => $member->id, 'text' => "Mã đơn hàng: DH3\nTên sản phẩm: YouTube\nEmail: new@example.com\nKhu vực bạn sống: HN\nNgày mua: 15/03/2026"],
        ]);
        FamilyHistoryLogger::logDiffs($family->id, $diffs);

        $history = History::where('family_id', $family->id)->first();
        $this->assertSame('change', $history->status);
        $this->assertSame('DH3', $history->order_id);
        $this->assertSame('new@example.com', $history->email);

        $old = json_decode($history->old_value, true);
        $new = json_decode($history->new_value, true);
        $this->assertSame('old@example.com', $old['email']);
        $this->assertSame('new@example.com', $new['email']);
        $this->assertArrayNotHasKey('raw_text', $old);
        $this->assertArrayNotHasKey('raw_text', $new);
        $this->assertSame('2026-01-01', $old['purchase_date']);
        $this->assertSame('2026-03-15 00:00:00', $new['purchase_date']);
    }
}
