<?php

namespace Tests\Feature\Console;

use App\Models\Family;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CutoverDropMemberColumnsTest extends TestCase
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
            'email' => 'owner@example.com', 'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank', 'user' => 'Nguyen Van A',
        ]);
    }

    public function test_dry_run_without_force_does_not_drop_columns(): void
    {
        $this->addMemberColumns();
        $this->makeFamily();

        $this->artisan('cutover:drop-member-columns')->assertExitCode(0);

        $this->assertTrue(Schema::hasColumn('families', 'member1'));
    }

    public function test_drops_columns_with_force_when_all_data_is_backfilled(): void
    {
        $this->addMemberColumns();
        $family = $this->makeFamily();
        $family->setAttribute('member1', json_encode(['order_code' => 'DH1', 'email' => 'a@example.com']));
        $family->save();
        $family->members()->create(['order_code' => 'DH1', 'email' => 'a@example.com']);

        $this->artisan('cutover:drop-member-columns', ['--force' => true])->assertExitCode(0);

        $this->assertFalse(Schema::hasColumn('families', 'member1'));
        $this->assertFalse(Schema::hasColumn('families', 'member5'));
    }

    public function test_refuses_to_drop_when_a_family_has_unbackfilled_member_data(): void
    {
        $this->addMemberColumns();
        $family = $this->makeFamily();
        $family->setAttribute('member1', json_encode(['order_code' => 'DH1', 'email' => 'a@example.com']));
        $family->save();
        // Deliberately no corresponding family_members row — simulates a missed backfill.

        $this->artisan('cutover:drop-member-columns', ['--force' => true])->assertExitCode(1);

        $this->assertTrue(Schema::hasColumn('families', 'member1'));
    }

    public function test_refuses_to_drop_when_a_family_is_only_partially_backfilled(): void
    {
        $this->addMemberColumns();
        $family = $this->makeFamily();
        $family->setAttribute('member1', json_encode(['order_code' => 'DH1', 'email' => 'a@example.com']));
        $family->setAttribute('member2', json_encode(['order_code' => 'DH2', 'email' => 'b@example.com']));
        $family->save();
        // Only member1 was backfilled — member2's data has no corresponding family_members row,
        // simulating an interrupted or incomplete backfill run.
        $family->members()->create(['order_code' => 'DH1', 'email' => 'a@example.com']);

        $this->artisan('cutover:drop-member-columns', ['--force' => true])->assertExitCode(1);

        $this->assertTrue(Schema::hasColumn('families', 'member1'));
    }
}
