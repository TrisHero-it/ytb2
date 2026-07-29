<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\History;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_the_family_and_its_members(): void
    {
        $user = User::factory()->create();
        $family = Family::create([
            'email' => 'owner@example.com', 'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank', 'user' => 'Nguyen Van A',
        ]);
        $member = $family->members()->create(['email' => 'a@example.com']);

        $response = $this->actingAs($user)->delete("/families/{$family->id}");

        $response->assertRedirect('/families');
        $this->assertDatabaseMissing('families', ['id' => $family->id]);
        $this->assertDatabaseMissing('family_members', ['id' => $member->id]);
    }

    public function test_deletes_a_family_that_has_history_entries(): void
    {
        $user = User::factory()->create();
        $family = Family::create([
            'email' => 'owner@example.com', 'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank', 'user' => 'Nguyen Van A',
        ]);
        $history = History::create([
            'order_id' => 'DH1',
            'family_id' => $family->id,
            'status' => 'add',
            'name_product' => 'YouTube',
            'email' => 'a@example.com',
        ]);

        $response = $this->actingAs($user)->delete("/families/{$family->id}");

        $response->assertRedirect('/families');
        $this->assertDatabaseMissing('families', ['id' => $family->id]);
        $this->assertDatabaseMissing('history_joining_family', ['id' => $history->id]);
    }
}
