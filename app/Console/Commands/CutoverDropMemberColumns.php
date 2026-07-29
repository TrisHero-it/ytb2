<?php

namespace App\Console\Commands;

use App\Models\Family;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CutoverDropMemberColumns extends Command
{
    protected $signature = 'cutover:drop-member-columns {--force : Actually drop the columns}';

    protected $description = 'Drop the legacy member1..member5 columns from families, after verifying every non-null slot was backfilled into family_members.';

    private const MEMBER_COLUMNS = ['member1', 'member2', 'member3', 'member4', 'member5'];

    public function handle(): int
    {
        $missingColumns = array_filter(
            self::MEMBER_COLUMNS,
            fn (string $column) => ! Schema::hasColumn('families', $column)
        );
        if ($missingColumns !== []) {
            $this->error('families is missing expected member columns: ' . implode(', ', $missingColumns));

            return self::FAILURE;
        }

        $familiesWithUnbackfilledData = Family::query()
            ->where(function ($query) {
                foreach (self::MEMBER_COLUMNS as $column) {
                    $query->orWhereNotNull($column);
                }
            })
            ->get()
            ->reject(function (Family $family) {
                $expectedCount = collect(self::MEMBER_COLUMNS)
                    ->filter(fn (string $column) => $family->getAttribute($column) !== null)
                    ->count();

                return $family->members()->count() >= $expectedCount;
            });

        if ($familiesWithUnbackfilledData->isNotEmpty()) {
            $ids = $familiesWithUnbackfilledData->pluck('id')->implode(', ');
            $this->error("Refusing to drop columns — families with unbackfilled member data: [{$ids}]. Run cutover:backfill-members first.");

            return self::FAILURE;
        }

        if (! $this->option('force')) {
            $this->info('Dry run — would drop columns: ' . implode(', ', self::MEMBER_COLUMNS));
            $this->info('Re-run with --force to actually drop them.');

            return self::SUCCESS;
        }

        Schema::table('families', function (Blueprint $table) {
            $table->dropColumn(self::MEMBER_COLUMNS);
        });

        $this->info('Dropped columns: ' . implode(', ', self::MEMBER_COLUMNS));

        return self::SUCCESS;
    }
}
