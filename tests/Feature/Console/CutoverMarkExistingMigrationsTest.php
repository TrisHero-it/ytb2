<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CutoverMarkExistingMigrationsTest extends TestCase
{
    use DatabaseMigrations;

    private function forgetMigrationRecords(): void
    {
        DB::table('migrations')->whereIn('migration', [
            '2026_07_24_041228_create_guild_collaborators_table',
            '2026_07_24_041444_create_history_joining_family_table',
        ])->delete();
    }

    public function test_marks_both_migrations_as_applied(): void
    {
        $this->forgetMigrationRecords();

        $this->artisan('cutover:mark-existing-migrations')->assertExitCode(0);

        $this->assertDatabaseHas('migrations', ['migration' => '2026_07_24_041228_create_guild_collaborators_table']);
        $this->assertDatabaseHas('migrations', ['migration' => '2026_07_24_041444_create_history_joining_family_table']);
    }

    public function test_is_safe_to_run_twice(): void
    {
        $this->forgetMigrationRecords();

        $this->artisan('cutover:mark-existing-migrations')->assertExitCode(0);
        $this->artisan('cutover:mark-existing-migrations')->assertExitCode(0);

        $this->assertSame(
            1,
            DB::table('migrations')->where('migration', '2026_07_24_041228_create_guild_collaborators_table')->count()
        );
    }

    public function test_refuses_to_mark_a_migration_for_a_table_that_does_not_exist(): void
    {
        $this->forgetMigrationRecords();
        Schema::drop('guild_collaborators');

        $this->artisan('cutover:mark-existing-migrations')->assertExitCode(1);

        $this->assertDatabaseMissing('migrations', ['migration' => '2026_07_24_041228_create_guild_collaborators_table']);
    }
}
