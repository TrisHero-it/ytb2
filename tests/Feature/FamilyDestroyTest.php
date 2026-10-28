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

    public function test_user_without_permission_cannot_delete_a_family(): void
    {
        $user = User::factory()->create(['can_delete_families' => false]);
        $family = Family::create([
            'email' => 'owner@example.com', 'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank', 'user' => 'Nguyen Van A',
        ]);

        $response = $this->actingAs($user)->delete("/families/{$family->id}");

        $response->assertRedirect('/families');
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('families', ['id' => $family->id]);
    }

    public function test_delete_button_is_hidden_for_user_without_permission(): void
    {
        $family = Family::create([
            'email' => 'owner@example.com', 'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank', 'user' => 'Nguyen Van A',
        ]);
        $deleteAction = 'action="'.route('families.destroy', $family).'"';

        $this->actingAs(User::factory()->create(['can_delete_families' => false]))
            ->get('/families')
            ->assertDontSee($deleteAction, false);

        $this->actingAs(User::factory()->create())
            ->get('/families')
            ->assertSee($deleteAction, false);
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
