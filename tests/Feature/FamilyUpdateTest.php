<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\User;
use App\Queries\FamilyListQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function makeFamily(): Family
    {
        return Family::create([
            'email' => 'owner@example.com', 'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank', 'user' => 'Nguyen Van A',
        ]);
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

    public function test_updates_family_fields(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();

        $response = $this->actingAs($user)->put("/families/{$family->id}", $this->validPayload($family, ['user' => 'Updated Name']));

        $response->assertRedirect('/families');
        $this->assertDatabaseHas('families', ['id' => $family->id, 'user' => 'Updated Name']);
    }

    public function test_updating_payment_at_only_saves_the_date_and_does_not_change_next_payment_date(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();
        $family->next_payment_at = '2026-10-04';
        $family->save();

        $beforeNextPayment = (new FamilyListQuery())->paginate()->items()[0]->next_payment_date;

        $this->actingAs($user)->put("/families/{$family->id}", $this->validPayload($family, [
            'payment_at' => '2026-05-01',
            'next_payment_at' => '2026-10-04',
        ]));

        $this->assertDatabaseHas('families', ['id' => $family->id, 'payment_at' => '2026-05-01']);

        $afterNextPayment = (new FamilyListQuery())->paginate()->items()[0]->next_payment_date;

        $this->assertSame($beforeNextPayment, $afterNextPayment);
    }

    public function test_adds_a_new_member(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();

        $this->actingAs($user)->put("/families/{$family->id}", $this->validPayload($family, [
            'member_texts' => [json_encode(['order_code' => 'NEW1', 'email' => 'new@example.com'])],
            'member_ids' => [null],
        ]));

        $this->assertDatabaseHas('family_members', ['family_id' => $family->id, 'order_code' => 'NEW1']);
    }

    public function test_removing_a_member_row_deletes_it(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();
        $member = $family->members()->create(['order_code' => 'GONE', 'email' => 'gone@example.com']);

        $this->actingAs($user)->put("/families/{$family->id}", $this->validPayload($family, [
            'member_texts' => [],
            'member_ids' => [],
        ]));

        $this->assertDatabaseMissing('family_members', ['id' => $member->id]);
    }

    public function test_editing_a_member_row_by_id_updates_it_in_place(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();
        $member = $family->members()->create(['order_code' => 'OLD', 'email' => 'old@example.com']);

        $this->actingAs($user)->put("/families/{$family->id}", $this->validPayload($family, [
            'member_texts' => [json_encode(['order_code' => 'OLD', 'email' => 'updated@example.com'])],
            'member_ids' => [$member->id],
        ]));

        $this->assertDatabaseHas('family_members', ['id' => $member->id, 'email' => 'updated@example.com']);
        $this->assertSame(1, $family->members()->count());
    }

    public function test_resubmitting_an_untouched_member_row_preserves_purchase_date(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();
        $member = $family->members()->create([
            'order_code' => 'KEEP',
            'email' => 'keep@example.com',
            'purchase_date' => '2026-03-15',
        ]);

        // Render the actual member-row partial, exactly as the edit form does,
        // and extract what the browser would resubmit unchanged for this member.
        $rendered = view('families.partials.member-row', ['member' => $member])->render();
        $this->assertMatchesRegularExpression('/<textarea[^>]*>(.*?)<\/textarea>/s', $rendered);
        preg_match('/<textarea[^>]*>(.*?)<\/textarea>/s', $rendered, $matches);
        $resubmittedText = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);

        $this->actingAs($user)->put("/families/{$family->id}", $this->validPayload($family, [
            'member_texts' => [$resubmittedText],
            'member_ids' => [$member->id],
        ]));

        $this->assertDatabaseHas('family_members', [
            'id' => $member->id,
            'purchase_date' => '2026-03-15',
        ]);
    }

    public function test_logs_change_history_when_editing_a_member(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();
        $member = $family->members()->create(['order_code' => 'OLD', 'email' => 'old@example.com', 'product_name' => 'YouTube']);

        $this->actingAs($user)->put("/families/{$family->id}", $this->validPayload($family, [
            'member_texts' => [json_encode(['order_code' => 'OLD', 'email' => 'updated@example.com', 'product_name' => 'YouTube'])],
            'member_ids' => [$member->id],
        ]));

        $this->assertDatabaseHas('history_joining_family', [
            'family_id' => $family->id,
            'order_id' => 'OLD',
            'status' => 'change',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_logs_delete_history_when_removing_a_member(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();
        $family->members()->create(['order_code' => 'GONE', 'email' => 'gone@example.com', 'product_name' => 'YouTube']);

        $this->actingAs($user)->put("/families/{$family->id}", $this->validPayload($family, [
            'member_texts' => [],
            'member_ids' => [],
        ]));

        $this->assertDatabaseHas('history_joining_family', [
            'family_id' => $family->id,
            'order_id' => 'GONE',
            'status' => 'delete',
            'email' => 'gone@example.com',
        ]);
    }
}
