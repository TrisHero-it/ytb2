<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FamilyMembersTableTest extends TestCase
{
    use RefreshDatabase;

    private function makeFamily(): int
    {
        return DB::table('families')->insertGetId([
            'email' => 'owner@example.com',
            'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank',
            'user' => 'Nguyen Van A',
        ]);
    }

    public function test_family_members_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('family_members'));
        $this->assertTrue(Schema::hasColumns('family_members', [
            'id', 'family_id', 'order_code', 'product_name', 'email',
            'region', 'purchase_date', 'raw_text', 'created_at', 'updated_at',
        ]));
    }

    public function test_deleting_a_family_cascades_to_its_members(): void
    {
        $familyId = $this->makeFamily();

        DB::table('family_members')->insert([
            'family_id' => $familyId,
            'order_code' => 'DH123',
            'email' => 'member@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('families')->where('id', $familyId)->delete();

        $this->assertSame(0, DB::table('family_members')->where('family_id', $familyId)->count());
    }
}
