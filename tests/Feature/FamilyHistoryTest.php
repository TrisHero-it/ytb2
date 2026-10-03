<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\History;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function makeFamily(array $overrides = []): Family
    {
        return Family::create(array_merge([
            'form' => 'ck', 'email' => 'owner@example.com', 'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank', 'user' => 'Nguyen Van A', 'status' => 'cho thanh toan',
            'pay_due_date' => '2026-08-01',
        ], $overrides));
    }

    /**
     * Lịch sử chứa email và mã đơn hàng của thành viên nên không được để lộ:
     * trước đây chỉ cần đoán id family là đọc được toàn bộ.
     */
    public function test_guest_cannot_read_the_history_endpoint(): void
    {
        $family = $this->makeFamily();
        History::create([
            'order_id' => 'DH1', 'family_id' => $family->id, 'status' => 'add',
            'name_product' => 'YouTube', 'email' => 'a@example.com',
        ]);

        $response = $this->getJson("/api/families/{$family->id}/history");

        $response->assertUnauthorized();
        $response->assertDontSee('a@example.com');
    }

    public function test_returns_history_rows_for_the_family_newest_first(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();
        $older = History::create([
            'order_id' => 'DH1', 'family_id' => $family->id, 'status' => 'add',
            'name_product' => 'YouTube', 'email' => 'a@example.com',
        ]);
        $newer = History::create([
            'order_id' => 'DH2', 'family_id' => $family->id, 'status' => 'add',
            'name_product' => 'YouTube', 'email' => 'b@example.com',
        ]);

        $response = $this->actingAs($user)->getJson("/api/families/{$family->id}/history");

        $response->assertOk();
        $ids = collect($response->json())->pluck('id')->all();
        $this->assertSame([$newer->id, $older->id], $ids);
    }

    public function test_only_returns_history_for_the_requested_family(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();
        $otherFamily = $this->makeFamily(['email' => 'other-owner@example.com']);
        History::create([
            'order_id' => 'DH1', 'family_id' => $family->id, 'status' => 'add',
            'name_product' => 'YouTube', 'email' => 'a@example.com',
        ]);
        History::create([
            'order_id' => 'DH9', 'family_id' => $otherFamily->id, 'status' => 'add',
            'name_product' => 'YouTube', 'email' => 'z@example.com',
        ]);

        $response = $this->actingAs($user)->getJson("/api/families/{$family->id}/history");

        $response->assertOk();
        $this->assertCount(1, $response->json());
        $this->assertSame('DH1', $response->json()[0]['order_id']);
    }
}
