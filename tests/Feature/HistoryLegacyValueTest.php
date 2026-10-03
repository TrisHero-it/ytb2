<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\History;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lịch sử từ phiên bản cũ lưu text thuần chứ không phải JSON (trong family_muakey
 * có 24 dòng như vậy). JSON.parse trần ném lỗi ở dòng đầu tiên gặp phải và cả
 * danh sách lịch sử hiện "Không tải được lịch sử".
 */
class HistoryLegacyValueTest extends TestCase
{
    use RefreshDatabase;

    private function makeFamily(): Family
    {
        return Family::create([
            'email' => 'owner@example.com', 'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank', 'user' => 'Nguyen Van A',
        ]);
    }

    public function test_the_page_parses_history_values_defensively(): void
    {
        $user = User::factory()->create();

        $html = $this->actingAs($user)->get('/families')->assertOk()->getContent();

        $this->assertStringContainsString('function historyParseValue(value)', $html);
        $this->assertStringNotContainsString('JSON.parse(item.old_value)', $html);
        $this->assertStringNotContainsString('JSON.parse(item.new_value)', $html);
    }

    public function test_a_legacy_plain_text_row_is_still_served_next_to_json_rows(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();

        History::create([
            'order_id' => '1183460', 'family_id' => $family->id, 'status' => 'change',
            'name_product' => 'YouTube', 'email' => 'a@example.com',
            'old_value' => json_encode(['order_code' => '1183460']),
            'new_value' => "Mã đơn hàng: 1183460\nEmail: a@example.com",
        ]);
        History::create([
            'order_id' => 'DH2', 'family_id' => $family->id, 'status' => 'add',
            'name_product' => 'YouTube', 'email' => 'b@example.com',
            'new_value' => json_encode(['order_code' => 'DH2']),
        ]);

        $response = $this->actingAs($user)->getJson("/api/families/{$family->id}/history");

        $response->assertOk();
        $this->assertCount(2, $response->json());
    }
}
