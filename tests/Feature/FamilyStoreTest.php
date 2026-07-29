<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyStoreTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'email' => 'owner@example.com',
            'user' => 'Nguyen Van A',
            'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank',
        ], $overrides);
    }

    public function test_creates_a_family_with_valid_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/families', $this->validPayload());

        $response->assertRedirect('/families');
        $this->assertDatabaseHas('families', ['email' => 'owner@example.com', 'user' => 'Nguyen Van A']);
    }

    public function test_validation_fails_without_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/families', []);

        $response->assertSessionHasErrors(['email', 'user', 'number_bank', 'name_bank']);
    }

    public function test_creates_members_from_pasted_text(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload([
            'member_texts' => ["Mã đơn hàng: DH1\nTên sản phẩm: YouTube\nEmail: a@example.com\nKhu vực bạn sống: HN\nNgày mua: 01/01/2026"],
            'member_ids' => [null],
        ]);

        $this->actingAs($user)->post('/families', $payload);

        $this->assertDatabaseHas('family_members', ['order_code' => 'DH1', 'email' => 'a@example.com']);
    }

    public function test_logs_add_history_when_creating_a_family_with_members(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload([
            'member_texts' => ["Mã đơn hàng: DH1\nTên sản phẩm: YouTube\nEmail: a@example.com\nKhu vực bạn sống: HN\nNgày mua: 01/01/2026"],
            'member_ids' => [null],
        ]);

        $this->actingAs($user)->post('/families', $payload);

        $family = Family::where('email', 'owner@example.com')->firstOrFail();
        $this->assertDatabaseHas('history_joining_family', [
            'family_id' => $family->id,
            'order_id' => 'DH1',
            'status' => 'add',
            'email' => 'a@example.com',
        ]);
    }

    public function test_rejects_duplicate_member_emails_within_the_same_submission(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload([
            'member_texts' => [
                json_encode(['email' => 'dup@example.com']),
                json_encode(['email' => 'dup@example.com']),
            ],
        ]);

        $response = $this->actingAs($user)->post('/families', $payload);

        $response->assertSessionHasErrors();
    }

    public function test_rejects_member_email_already_used_by_another_family(): void
    {
        $user = User::factory()->create();
        $existing = Family::create($this->validPayload(['email' => 'existing-owner@example.com']));
        $existing->members()->create(['email' => 'claimed@example.com']);

        $response = $this->actingAs($user)->post('/families', $this->validPayload([
            'member_texts' => [json_encode(['email' => 'claimed@example.com'])],
        ]));

        $response->assertSessionHasErrors();
    }
}
