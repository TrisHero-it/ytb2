<?php

namespace Tests\Feature\Models;

use App\Models\Family;
use App\Models\History;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoryModelTest extends TestCase
{
    use RefreshDatabase;

    private function makeFamily(): Family
    {
        return Family::create([
            'form' => 'ck', 'email' => 'owner@example.com', 'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank', 'user' => 'Nguyen Van A', 'status' => 'cho thanh toan',
            'pay_due_date' => '2026-08-01',
        ]);
    }

    public function test_history_can_be_created_and_created_at_is_set(): void
    {
        $family = $this->makeFamily();

        $history = History::create([
            'order_id' => 'ORD001',
            'family_id' => $family->id,
            'status' => 'add',
            'name_product' => 'YouTube Premium',
            'email' => 'member@example.com',
        ]);

        $this->assertDatabaseHas('history_joining_family', [
            'id' => $history->id,
            'order_id' => 'ORD001',
            'family_id' => $family->id,
            'status' => 'add',
        ]);
        $this->assertNotNull($history->fresh()->created_at);
    }

    public function test_maps_to_history_joining_family_table(): void
    {
        $history = new History();

        $this->assertSame('history_joining_family', $history->getTable());
    }

    public function test_has_no_updated_at_column(): void
    {
        $this->assertNull(History::UPDATED_AT);
    }
}
