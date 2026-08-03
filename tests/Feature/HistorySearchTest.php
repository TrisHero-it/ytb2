<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\History;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistorySearchTest extends TestCase
{
    use RefreshDatabase;

    private function makeFamily(array $overrides = []): Family
    {
        return Family::create(array_merge([
            'email' => 'owner@example.com', 'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank', 'user' => 'Nguyen Van A',
        ], $overrides));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->getJson('/api/history/search?q=test');

        $response->assertUnauthorized();
    }

    public function test_returns_empty_array_when_query_is_blank(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/history/search?q=');

        $response->assertOk();
        $this->assertSame([], $response->json());
    }

    public function test_matches_by_member_email(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();
        History::create([
            'order_id' => 'DH1', 'family_id' => $family->id, 'status' => 'add',
            'name_product' => 'YouTube', 'email' => 'findme@example.com',
        ]);

        $response = $this->actingAs($user)->getJson('/api/history/search?q=findme');

        $response->assertOk();
        $this->assertCount(1, $response->json());
        $this->assertSame('findme@example.com', $response->json()[0]['email']);
    }

    public function test_matches_by_order_code(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();
        History::create([
            'order_id' => 'DH999', 'family_id' => $family->id, 'status' => 'add',
            'name_product' => 'YouTube', 'email' => 'a@example.com',
        ]);

        $response = $this->actingAs($user)->getJson('/api/history/search?q=DH999');

        $response->assertOk();
        $this->assertCount(1, $response->json());
    }

    public function test_matches_by_family_owner_name_and_includes_it_in_the_response(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily(['user' => 'Nha Ba Tam']);
        History::create([
            'order_id' => 'DH1', 'family_id' => $family->id, 'status' => 'add',
            'name_product' => 'YouTube', 'email' => 'a@example.com',
        ]);

        $response = $this->actingAs($user)->getJson('/api/history/search?q=Ba+Tam');

        $response->assertOk();
        $this->assertCount(1, $response->json());
        $this->assertSame('Nha Ba Tam', $response->json()[0]['family_user']);
    }

    public function test_does_not_match_unrelated_families(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();
        $otherFamily = $this->makeFamily(['email' => 'other@example.com', 'user' => 'Other Family']);
        History::create([
            'order_id' => 'DH1', 'family_id' => $family->id, 'status' => 'add',
            'name_product' => 'YouTube', 'email' => 'a@example.com',
        ]);
        History::create([
            'order_id' => 'DH9', 'family_id' => $otherFamily->id, 'status' => 'add',
            'name_product' => 'YouTube', 'email' => 'z@example.com',
        ]);

        $response = $this->actingAs($user)->getJson('/api/history/search?q=DH1');

        $response->assertOk();
        $this->assertCount(1, $response->json());
        $this->assertSame('DH1', $response->json()[0]['order_id']);
    }

    public function test_results_are_ordered_newest_first(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();
        $older = History::create([
            'order_id' => 'DH-SEARCH', 'family_id' => $family->id, 'status' => 'add',
            'name_product' => 'YouTube', 'email' => 'a@example.com',
        ]);
        $newer = History::create([
            'order_id' => 'DH-SEARCH', 'family_id' => $family->id, 'status' => 'add',
            'name_product' => 'YouTube', 'email' => 'b@example.com',
        ]);

        $response = $this->actingAs($user)->getJson('/api/history/search?q=DH-SEARCH');

        $response->assertOk();
        $ids = collect($response->json())->pluck('id')->all();
        $this->assertSame([$newer->id, $older->id], $ids);
    }
}
