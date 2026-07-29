<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CutoverRenameFamilyTable extends Command
{
    protected $signature = 'cutover:rename-family-table';

    protected $description = 'Rename the legacy family table to families, then mark create_families_table as already applied.';

    private const MIGRATION = '2026_07_24_040644_create_families_table';

    public function handle(): int
    {
        if (! Schema::hasTable('family')) {
            $this->error('Table [family] does not exist — nothing to rename.');

            return self::FAILURE;
        }

        if (Schema::hasTable('families')) {
            $this->error('Table [families] already exists — refusing to rename over it.');

            return self::FAILURE;
        }

        DB::statement('RENAME TABLE family TO families');
        $this->info('Renamed [family] to [families].');

        if (! DB::table('migrations')->where('migration', self::MIGRATION)->exists()) {
            $batch = (int) (DB::table('migrations')->max('batch') ?? 0) + 1;
            DB::table('migrations')->insert(['migration' => self::MIGRATION, 'batch' => $batch]);
            $this->info('Marked as applied: ' . self::MIGRATION);
        }

        return self::SUCCESS;
    }
}
