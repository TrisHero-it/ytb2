<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FamiliesTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_families_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('families'));
        $this->assertTrue(Schema::hasColumns('families', [
            'id', 'payment_at', 'next_payment_at', 'email', 'number_phone', 'number_bank',
            'name_bank', 'user', 'monthly_payment', 'auto_payment_day',
            'afiilicate_by', 'bill_of_master', 'bill_payment', 'note',
        ]));
    }

    public function test_family_row_can_be_inserted_and_read_back(): void
    {
        $id = DB::table('families')->insertGetId([
            'email' => 'owner@example.com',
            'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank',
            'user' => 'Nguyen Van A',
        ]);

        $row = DB::table('families')->find($id);

        $this->assertSame('owner@example.com', $row->email);
        $this->assertSame(1, (int) $row->monthly_payment);
        $this->assertNull($row->auto_payment_day);
    }
}
