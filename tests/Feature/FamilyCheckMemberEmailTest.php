<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyCheckMemberEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_exists_false_for_unused_email(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/families/check-member-email?email=new@example.com');

        $response->assertOk()->assertJson(['exists' => false, 'family' => null]);
    }

    public function test_returns_exists_true_for_owner_email(): void
    {
        $user = User::factory()->create();
        $family = Family::create([
            'email' => 'owner@example.com', 'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank', 'user' => 'Nguyen Van A',
        ]);

        $response = $this->actingAs($user)->getJson('/api/families/check-member-email?email=owner@example.com');

        $response->assertOk()->assertJson(['exists' => true, 'family' => ['id' => $family->id, 'user' => 'Nguyen Van A', 'email' => 'owner@example.com']]);
    }

    public function test_returns_exists_true_for_member_email(): void
    {
        $user = User::factory()->create();
        $family = Family::create([
            'email' => 'owner@example.com', 'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank', 'user' => 'Nguyen Van A',
        ]);
        $family->members()->create(['email' => 'member@example.com']);

        $response = $this->actingAs($user)->getJson('/api/families/check-member-email?email=member@example.com');

        $response->assertOk()->assertJson(['exists' => true, 'family' => ['id' => $family->id]]);
    }

    public function test_excludes_given_family_id(): void
    {
        $user = User::factory()->create();
        $family = Family::create([
            'email' => 'owner@example.com', 'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank', 'user' => 'Nguyen Van A',
        ]);

        $response = $this->actingAs($user)->getJson("/api/families/check-member-email?email=owner@example.com&exclude_id={$family->id}");

        $response->assertOk()->assertJson(['exists' => false, 'family' => null]);
    }

    public function test_guest_receives_unauthorized_json(): void
    {
        $response = $this->get('/api/families/check-member-email?email=x@example.com');

        $response->assertStatus(401);
    }
}
