<?php

namespace Tests\Feature\Console;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CutoverRenameFamilyTableTest extends TestCase
{
    use DatabaseMigrations;

    private function simulatePreCutoverState(): void
    {
        // history_joining_family still holds a live FK to `families` at this point
        // (mirrors production: it references whatever the family table is currently
        // named), so dropping `families` here needs the same FK_CHECKS toggle already
        // used in the create_families_table / create_history_joining_family_table
        // migrations' down() methods.
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Schema::dropIfExists('family_members');
        Schema::dropIfExists('families');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        DB::table('migrations')->whereIn('migration', [
            '2026_07_24_040644_create_families_table',
            '2026_07_24_040946_create_family_members_table',
        ])->delete();

        Schema::create('family', function (Blueprint $table) {
            $table->id();
            $table->string('form');
            $table->date('payment_at')->nullable();
            $table->string('email');
            $table->string('number_phone', 20)->nullable();
            $table->string('number_bank', 100);
            $table->string('name_bank', 100);
            $table->string('user');
            $table->integer('monthly_payment')->default(0);
            $table->integer('month_master_pay')->default(1);
            $table->integer('month_to_pay')->nullable();
            $table->string('afiilicate_by')->nullable();
            $table->text('bill_of_master')->nullable();
            $table->text('bill_payment')->nullable();
            $table->string('status');
            $table->date('pay_due_date');
            $table->text('note')->nullable();
            $table->text('member1')->nullable();
            $table->text('member2')->nullable();
            $table->text('member3')->nullable();
            $table->text('member4')->nullable();
            $table->text('member5')->nullable();
        });

        DB::table('family')->insert([
            'form' => 'ck', 'email' => 'owner@example.com', 'number_bank' => '0123456789',
            'name_bank' => 'Vietcombank', 'user' => 'Nguyen Van A', 'status' => 'cho thanh toan',
            'pay_due_date' => '2026-08-01',
            'member1' => json_encode(['order_code' => 'DH1', 'email' => 'a@example.com']),
        ]);
    }

    public function test_renames_family_to_families_preserving_data_and_ids(): void
    {
        $this->simulatePreCutoverState();
        $originalId = DB::table('family')->value('id');

        $this->artisan('cutover:rename-family-table')->assertExitCode(0);

        $this->assertFalse(Schema::hasTable('family'));
        $this->assertTrue(Schema::hasTable('families'));
        $this->assertDatabaseHas('families', [
            'id' => $originalId,
            'email' => 'owner@example.com',
            'member1' => json_encode(['order_code' => 'DH1', 'email' => 'a@example.com']),
        ]);
    }

    public function test_marks_create_families_table_migration_as_applied(): void
    {
        $this->simulatePreCutoverState();

        $this->artisan('cutover:rename-family-table')->assertExitCode(0);

        $this->assertDatabaseHas('migrations', ['migration' => '2026_07_24_040644_create_families_table']);
    }

    public function test_refuses_to_run_if_families_already_exists(): void
    {
        // Default post-migrate state already has `families` (and no `family`) —
        // exactly the "already renamed" case this command must refuse to redo.
        $this->artisan('cutover:rename-family-table')->assertExitCode(1);

        $this->assertTrue(Schema::hasTable('families'));
    }
}
