<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Giao diện ghi "x / 5" và đỏ lên khi đủ, nhưng server từng nhận bao nhiêu
 * thành viên cũng được.
 */
class MemberLimitTest extends TestCase
{
    use RefreshDatabase;

    private function makeFamily(): Family
    {
        return Family::create([
            'email' => 'owner@example.com', 'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank', 'user' => 'Nguyen Van A',
        ]);
    }

    /** @return array<int, string> */
    private function memberTexts(int $count): array
    {
        return collect(range(1, $count))
            ->map(fn (int $i) => json_encode(['order_code' => "DH{$i}", 'email' => "m{$i}@example.com"]))
            ->all();
    }

    private function payload(Family $family, int $memberCount): array
    {
        return [
            'email' => $family->email, 'user' => $family->user,
            'number_bank' => $family->number_bank, 'name_bank' => $family->name_bank,
            'member_texts' => $this->memberTexts($memberCount),
            'member_ids' => array_fill(0, $memberCount, null),
        ];
    }

    public function test_five_members_are_accepted(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();

        $response = $this->actingAs($user)->put("/families/{$family->id}", $this->payload($family, 5));

        $response->assertSessionHasNoErrors();
        $this->assertSame(5, $family->members()->count());
    }

    public function test_a_sixth_member_is_refused(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();

        $response = $this->actingAs($user)->put("/families/{$family->id}", $this->payload($family, 6));

        $response->assertSessionHasErrors('member_texts');
        $this->assertStringContainsString('chỉ chứa được 5 thành viên', session('errors')->first('member_texts'));
        $this->assertSame(0, $family->members()->count());
    }

    public function test_creating_a_family_with_six_members_is_refused(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/families', [
            'email' => 'new@example.com', 'user' => 'Tran Thi B',
            'number_bank' => '999', 'name_bank' => 'Vietcombank',
            'member_texts' => $this->memberTexts(6),
        ]);

        $response->assertSessionHasErrors('member_texts');
        $this->assertDatabaseMissing('families', ['email' => 'new@example.com']);
    }

    /** Dòng trống trong form không phải thành viên, không được tính vào giới hạn. */
    public function test_blank_rows_do_not_count_towards_the_limit(): void
    {
        $user = User::factory()->create();
        $family = $this->makeFamily();

        $payload = $this->payload($family, 5);
        $payload['member_texts'][] = '   ';
        $payload['member_ids'][] = null;

        $response = $this->actingAs($user)->put("/families/{$family->id}", $payload);

        $response->assertSessionHasNoErrors();
        $this->assertSame(5, $family->members()->count());
    }
}
