<?php

namespace Tests\Feature\Console;

use App\Models\Family;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CutoverBackfillMembersTest extends TestCase
{
    use DatabaseMigrations;

    private function addMemberColumns(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->text('member1')->nullable();
            $table->text('member2')->nullable();
            $table->text('member3')->nullable();
            $table->text('member4')->nullable();
            $table->text('member5')->nullable();
        });
    }

    private function makeFamily(): Family
    {
        return Family::create([
            'form' => 'ck', 'email' => 'owner@example.com', 'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank', 'user' => 'Nguyen Van A', 'status' => 'cho thanh toan',
            'pay_due_date' => '2026-08-01',
        ]);
    }

    public function test_backfills_members_from_json_columns(): void
    {
        $this->addMemberColumns();
        $family = $this->makeFamily();
        $family->setAttribute('member1', json_encode([
            'order_code' => 'DH1', 'product_name' => 'YouTube', 'email' => 'a@example.com',
            'region' => 'HN', 'purchase_date' => '18:54:15 19/01/2026',
        ]));
        $family->save();

        $this->artisan('cutover:backfill-members')->assertExitCode(0);

        $this->assertDatabaseHas('family_members', [
            'family_id' => $family->id,
            'order_code' => 'DH1',
            'email' => 'a@example.com',
            'purchase_date' => '2026-01-19',
        ]);
        $this->assertSame(1, $family->members()->count());
    }

    public function test_skips_null_member_columns(): void
    {
        $this->addMemberColumns();
        $family = $this->makeFamily();

        $this->artisan('cutover:backfill-members')->assertExitCode(0);

        $this->assertSame(0, $family->members()->count());
    }

    public function test_skips_families_that_already_have_members(): void
    {
        $this->addMemberColumns();
        $family = $this->makeFamily();
        $family->setAttribute('member1', json_encode(['order_code' => 'DH1', 'email' => 'a@example.com']));
        $family->save();
        $family->members()->create(['order_code' => 'EXISTING', 'email' => 'existing@example.com']);

        $this->artisan('cutover:backfill-members')->assertExitCode(0);

        $this->assertSame(1, $family->members()->count());
        $this->assertDatabaseMissing('family_members', ['order_code' => 'DH1']);
    }
}
