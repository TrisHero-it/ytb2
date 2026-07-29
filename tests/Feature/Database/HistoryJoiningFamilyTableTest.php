<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HistoryJoiningFamilyTableTest extends TestCase
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

    public function test_history_joining_family_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('history_joining_family'));
        $this->assertTrue(Schema::hasColumns('history_joining_family', [
            'id', 'order_id', 'family_id', 'status', 'old_value', 'new_value', 'name_product', 'email', 'created_at',
        ]));
    }

    public function test_deleting_a_family_cascades_to_its_history(): void
    {
        $familyId = $this->makeFamily();

        $historyId = DB::table('history_joining_family')->insertGetId([
            'order_id' => 'DH123',
            'family_id' => $familyId,
            'name_product' => 'YouTube Premium 12 thang',
            'email' => 'member@example.com',
        ]);

        DB::table('families')->where('id', $familyId)->delete();

        $this->assertDatabaseMissing('history_joining_family', ['id' => $historyId]);
    }
}
