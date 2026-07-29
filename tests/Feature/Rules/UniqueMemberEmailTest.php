<?php

namespace Tests\Feature\Rules;

use App\Models\Family;
use App\Rules\UniqueMemberEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UniqueMemberEmailTest extends TestCase
{
    use RefreshDatabase;

    private function makeFamily(array $overrides = []): Family
    {
        return Family::create(array_merge([
            'form' => 'ck',
            'email' => 'owner@example.com',
            'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank',
            'user' => 'Nguyen Van A',
            'status' => 'cho thanh toan',
            'pay_due_date' => '2026-08-01',
        ], $overrides));
    }

    public function test_fails_when_email_matches_another_familys_owner_email(): void
    {
        $this->makeFamily(['email' => 'taken@example.com']);

        $validator = Validator::make(['email' => 'taken@example.com'], ['email' => [new UniqueMemberEmail()]]);

        $this->assertTrue($validator->fails());
    }

    public function test_fails_when_email_matches_another_familys_member_email(): void
    {
        $family = $this->makeFamily();
        $family->members()->create(['email' => 'member@example.com']);

        $validator = Validator::make(['email' => 'member@example.com'], ['email' => [new UniqueMemberEmail()]]);

        $this->assertTrue($validator->fails());
    }

    public function test_passes_when_email_belongs_to_the_excluded_family(): void
    {
        $family = $this->makeFamily(['email' => 'owner@example.com']);

        $validator = Validator::make(['email' => 'owner@example.com'], ['email' => [new UniqueMemberEmail($family->id)]]);

        $this->assertFalse($validator->fails());
    }

    public function test_passes_for_a_brand_new_email(): void
    {
        $validator = Validator::make(['email' => 'new@example.com'], ['email' => [new UniqueMemberEmail()]]);

        $this->assertFalse($validator->fails());
    }

    public function test_is_case_and_whitespace_insensitive(): void
    {
        $this->makeFamily(['email' => 'taken@example.com']);

        $validator = Validator::make(['email' => '  TAKEN@EXAMPLE.COM  '], ['email' => [new UniqueMemberEmail()]]);

        $this->assertTrue($validator->fails());
    }
}
