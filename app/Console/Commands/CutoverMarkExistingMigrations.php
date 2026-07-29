<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CutoverMarkExistingMigrations extends Command
{
    protected $signature = 'cutover:mark-existing-migrations';

    protected $description = 'Mark create_guild_collaborators_table and create_history_joining_family_table as already applied, since those tables already exist on the live database with a matching schema.';

    private const TABLES = [
        '2026_07_24_041228_create_guild_collaborators_table' => 'guild_collaborators',
        '2026_07_24_041444_create_history_joining_family_table' => 'history_joining_family',
    ];

    public function handle(): int
    {
        foreach (self::TABLES as $migration => $table) {
            if (! Schema::hasTable($table)) {
                $this->error("Table [{$table}] does not exist — refusing to mark [{$migration}] as applied.");

                return self::FAILURE;
            }
        }

        $batch = (int) (DB::table('migrations')->max('batch') ?? 0) + 1;
        $marked = 0;

        foreach (self::TABLES as $migration => $table) {
            if (DB::table('migrations')->where('migration', $migration)->exists()) {
                $this->line("Already marked: {$migration}");

                continue;
            }

            DB::table('migrations')->insert([
                'migration' => $migration,
                'batch' => $batch,
            ]);
            $this->info("Marked as applied: {$migration}");
            $marked++;
        }

        $this->info("Done. {$marked} migration(s) newly marked as applied.");

        return self::SUCCESS;
    }
}
