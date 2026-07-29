<?php

namespace Tests\Feature\Models;

use App\Models\Family;
use App\Models\FamilyMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyModelTest extends TestCase
{
    use RefreshDatabase;

    private function makeFamily(array $overrides = []): Family
    {
        return Family::create(array_merge([
            'email' => 'owner@example.com',
            'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank',
            'user' => 'Nguyen Van A',
        ], $overrides));
    }

    public function test_family_can_be_created_without_timestamp_columns(): void
    {
        $family = $this->makeFamily();

        $this->assertDatabaseHas('families', ['id' => $family->id, 'email' => 'owner@example.com']);
    }

    public function test_family_has_many_members_ordered_by_id(): void
    {
        $family = $this->makeFamily();
        $firstCreated = FamilyMember::create(['family_id' => $family->id, 'email' => 'a@example.com']);
        $secondCreated = FamilyMember::create(['family_id' => $family->id, 'email' => 'b@example.com']);

        $family->refresh();

        $this->assertSame([$firstCreated->id, $secondCreated->id], $family->members->pluck('id')->all());
    }

    public function test_family_member_belongs_to_family(): void
    {
        $family = $this->makeFamily();
        $member = FamilyMember::create(['family_id' => $family->id, 'email' => 'a@example.com']);

        $this->assertTrue($member->family->is($family));
    }

    public function test_payment_at_is_cast_to_date(): void
    {
        $family = $this->makeFamily(['payment_at' => '2026-08-01']);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $family->payment_at);
    }
}
